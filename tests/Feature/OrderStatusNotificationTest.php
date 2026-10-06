<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Customer;
use App\Models\Order;
use App\Services\Orders\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class OrderStatusNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(string $type = 'delivery'): Order
    {
        $customer = Customer::create([
            'name' => 'Gita',
            'phone_number' => '9779800002222',
        ]);

        return Order::create([
            'order_number' => Order::generateOrderNumber(),
            'customer_id' => $customer->id,
            'order_type' => $type,
            'status' => OrderStatus::Pending,
            'subtotal_amount' => 500,
            'delivery_fee' => 100,
            'total_amount' => 600,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
        ]);
    }

    public function test_confirming_an_order_notifies_the_customer(): void
    {
        Bus::fake([SendWhatsAppMessage::class]);
        $order = $this->makeOrder();

        app(OrderStatusService::class)->transition($order, OrderStatus::Confirmed);

        Bus::assertDispatched(SendWhatsAppMessage::class, function (SendWhatsAppMessage $job) use ($order) {
            return $job->to === '9779800002222'
                && str_contains($job->message['body'], $order->order_number)
                && str_contains($job->message['body'], 'confirmed');
        });
    }

    public function test_out_for_delivery_updates_tracking_and_notifies(): void
    {
        Bus::fake([SendWhatsAppMessage::class]);
        $order = $this->makeOrder();
        $service = app(OrderStatusService::class);

        $service->transition($order, OrderStatus::Confirmed);
        $service->transition($order->fresh(), OrderStatus::Preparing);
        $service->transition($order->fresh(), OrderStatus::OutForDelivery);

        $this->assertSame('out_for_delivery', $order->fresh()->deliveryTracking->status->value);

        Bus::assertDispatched(SendWhatsAppMessage::class, function (SendWhatsAppMessage $job) {
            return str_contains($job->message['body'], 'on the way');
        });
    }

    public function test_illegal_transition_sends_no_notification(): void
    {
        Bus::fake([SendWhatsAppMessage::class]);
        $order = $this->makeOrder();

        $this->expectException(\InvalidArgumentException::class);

        try {
            app(OrderStatusService::class)->transition($order, OrderStatus::Delivered);
        } finally {
            Bus::assertNotDispatched(SendWhatsAppMessage::class);
        }
    }
}
