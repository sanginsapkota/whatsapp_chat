<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Filament\Rider\Pages\MyDeliveries;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Customer;
use App\Models\DeliveryTracking;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class RiderDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function scenario(): array
    {
        $rider = User::factory()->create(['role' => UserRole::Rider]);
        $customer = Customer::create(['name' => 'Bishnu', 'phone_number' => '9779811112222']);

        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'customer_id' => $customer->id,
            'order_type' => 'delivery',
            'status' => OrderStatus::Pending,
            'subtotal_amount' => 800,
            'delivery_fee' => 100,
            'total_amount' => 900,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
        ]);

        $order->payment()->create([
            'payment_method' => 'cod',
            'amount' => $order->total_amount,
            'payment_status' => 'pending',
        ]);

        $tracking = $order->deliveryTracking()->create(['status' => 'preparing', 'rider_id' => $rider->id]);

        $service = app(OrderStatusService::class);
        $service->transition($order, OrderStatus::Confirmed);
        $service->transition($order->fresh(), OrderStatus::Preparing);

        return [$rider, $order->fresh(), $tracking->fresh()];
    }

    public function test_assigning_a_rider_stamps_assigned_at_and_status(): void
    {
        [, , $tracking] = $this->scenario();

        $this->assertNotNull($tracking->assigned_at);
        $this->assertSame('assigned', $tracking->status->value);
    }

    public function test_rider_can_see_only_their_active_deliveries(): void
    {
        [$rider, $order] = $this->scenario();
        $otherRider = User::factory()->create(['role' => UserRole::Rider]);
        $other = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'customer_id' => $order->customer_id,
            'order_type' => 'delivery',
            'status' => OrderStatus::Pending,
            'subtotal_amount' => 100, 'delivery_fee' => 0, 'total_amount' => 100,
            'payment_method' => 'cod', 'payment_status' => 'pending',
        ]);
        $other->deliveryTracking()->create(['status' => 'preparing', 'rider_id' => $otherRider->id]);

        Livewire::actingAs($rider)
            ->test(MyDeliveries::class)
            ->assertCanSeeTableRecords(DeliveryTracking::where('rider_id', $rider->id)->get())
            ->assertCanNotSeeTableRecords(DeliveryTracking::where('rider_id', $otherRider->id)->get());
    }

    public function test_rider_marks_an_order_out_for_delivery_then_delivered(): void
    {
        Bus::fake([SendWhatsAppMessage::class]);
        [$rider, $order, $tracking] = $this->scenario();

        Livewire::actingAs($rider)
            ->test(MyDeliveries::class)
            ->callTableAction('startDelivery', $tracking->getKey());

        $this->assertSame(OrderStatus::OutForDelivery, $order->fresh()->status);

        Livewire::actingAs($rider)
            ->test(MyDeliveries::class)
            ->callTableAction('markDelivered', $tracking->getKey(), data: ['location' => 'Handed at gate']);

        $order->refresh();
        $this->assertSame(OrderStatus::Delivered, $order->status);
        $this->assertNotNull($order->deliveryTracking->delivered_at);
        $this->assertSame('paid', $order->payment->payment_status->value);

        Bus::assertDispatched(SendWhatsAppMessage::class, fn (SendWhatsAppMessage $j) => str_contains($j->message['body'], 'delivered'));
    }

    public function test_guest_is_redirected_from_the_rider_panel(): void
    {
        $this->get('/rider/my-deliveries')->assertRedirectContains('/rider/login');
    }
}
