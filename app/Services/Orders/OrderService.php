<?php

namespace App\Services\Orders;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Order;
use App\Models\WhatsappSession;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderService
{
    public function __construct(private readonly DeliveryFeeCalculator $fees) {}

    /**
     * Build a real order from a completed chatbot session's context_data cart.
     */
    public function createFromSession(WhatsappSession $session): Order
    {
        $context = $session->context_data ?? [];
        $cart = Arr::get($context, 'cart', []);
        $customer = $session->customer;

        if ($customer === null) {
            throw new RuntimeException("Session {$session->id} has no customer.");
        }

        if (empty($cart)) {
            throw new RuntimeException("Session {$session->id} has an empty cart.");
        }

        $fulfilment = Arr::get($context, 'fulfilment', 'delivery');
        $subtotal = round((float) collect($cart)->sum('line_total'), 2);
        $deliveryFee = $this->fees->for($subtotal, $fulfilment);

        return DB::transaction(function () use ($context, $cart, $customer, $fulfilment, $subtotal, $deliveryFee) {
            $address = $fulfilment === 'delivery'
                ? $this->resolveAddress($customer, $context)
                : null;

            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'customer_id' => $customer->id,
                'address_id' => $address?->id,
                'order_type' => $fulfilment === 'pickup' ? OrderType::Pickup : OrderType::Delivery,
                'status' => OrderStatus::Pending,
                'subtotal_amount' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total_amount' => round($subtotal + $deliveryFee, 2),
                'payment_method' => PaymentMethod::Cod,
                'payment_status' => PaymentStatus::Pending,
                'notes' => Arr::get($context, 'notes'),
                'ordered_at' => now(),
            ]);

            foreach ($cart as $item) {
                $order->items()->create([
                    'product_variant_id' => $item['variant_id'],
                    'product_name' => $item['product_name'],
                    'variant_name' => $item['variant_name'],
                    'unit' => $item['unit'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                    'preparation_type' => $item['preparation'] ?? null,
                ]);
            }

            $order->payment()->create([
                'payment_method' => PaymentMethod::Cod,
                'amount' => $order->total_amount,
                'payment_status' => PaymentStatus::Pending,
            ]);

            if ($order->order_type === OrderType::Delivery) {
                $order->deliveryTracking()->create(['status' => DeliveryStatus::Preparing]);
            }

            $customer->forceFill(['last_ordered_at' => now()])->save();

            return $order->load('items');
        });
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolveAddress(Customer $customer, array $context): ?Address
    {
        if ($id = Arr::get($context, 'address_id')) {
            return $customer->addresses()->whereKey($id)->first();
        }

        if ($location = Arr::get($context, 'location')) {
            return $customer->addresses()->create([
                'label' => 'Location pin',
                'address_line1' => 'Shared location pin',
                'latitude' => $location['lat'] ?? null,
                'longitude' => $location['lng'] ?? null,
            ]);
        }

        if ($text = Arr::get($context, 'new_address')) {
            return $customer->addresses()->create([
                'address_line1' => $text,
            ]);
        }

        return null;
    }
}
