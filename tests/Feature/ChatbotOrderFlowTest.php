<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SessionStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\WhatsappSession;
use App\Services\Chatbot\FlowEngine;
use App\Services\Chatbot\InboundMessage;
use App\Services\WhatsApp\OutboundMessage;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\ChatbotFlowStepSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private WhatsappSession $session;

    private Customer $customer;

    private int $wam = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CatalogSeeder::class, ChatbotFlowStepSeeder::class]);

        config()->set('ordering.delivery_fee', 100);

        $this->customer = Customer::create([
            'name' => 'Hari Prasad',
            'phone_number' => '9779800001111',
        ]);

        $this->session = WhatsappSession::create([
            'phone_number' => '9779800001111',
            'customer_id' => $this->customer->id,
            'session_status' => SessionStatus::Active,
            'current_step' => 'welcome',
            'context_data' => [],
            'last_message_at' => now(),
        ]);
    }

    /**
     * @return array<int, OutboundMessage>
     */
    private function send(?string $text = null, ?string $replyId = null, ?array $location = null): array
    {
        $message = new InboundMessage(
            wamId: 'wamid.'.(++$this->wam),
            from: $this->session->phone_number,
            type: $location ? 'location' : ($replyId ? 'interactive' : 'text'),
            text: $text,
            replyId: $replyId,
            latitude: $location['lat'] ?? null,
            longitude: $location['lng'] ?? null,
        );

        $replies = app(FlowEngine::class)->handle($this->session->fresh(), $message);
        $this->session = $this->session->fresh();

        return $replies;
    }

    public function test_full_delivery_order_is_created_from_the_conversation(): void
    {
        $category = Category::active()->ordered()->first();
        $product = $category->products()->active()->ordered()->first();
        $variant = $product->variants()->active()->first();

        $this->send(text: 'hi');
        $this->assertSame('browse_categories', $this->session->current_step);

        $this->send(replyId: "cat:{$category->id}");
        $this->assertSame('list_products', $this->session->current_step);

        $this->send(replyId: "prod:{$product->id}");
        $this->assertSame('select_variant', $this->session->current_step);

        $this->send(replyId: "var:{$variant->id}");
        $this->assertSame('enter_quantity', $this->session->current_step);

        $this->send(text: '2');
        $this->assertSame('select_preparation', $this->session->current_step);

        $this->send(replyId: 'prep:small');
        $this->assertSame('cart_review', $this->session->current_step);
        $this->assertCount(1, $this->session->context_data['cart']);

        $this->send(replyId: 'cart:checkout');
        $this->assertSame('choose_fulfillment', $this->session->current_step);

        $this->send(replyId: 'fulfil:delivery');
        $this->assertSame('choose_address', $this->session->current_step);

        $this->send(text: 'Baneshwor, near the chowk, ward 10, blue gate');
        $this->assertSame('confirm_order', $this->session->current_step);

        $this->send(replyId: 'confirm:yes');
        $this->assertSame('choose_payment', $this->session->current_step);

        $this->send(replyId: 'pay:cod');

        $this->session = $this->session->fresh();
        $this->assertSame(SessionStatus::Completed, $this->session->session_status);

        $order = Order::with('items', 'payment', 'deliveryTracking', 'address')->firstOrFail();

        $expectedLine = round(2 * (float) $variant->price_per_unit, 2);

        $this->assertSame($this->customer->id, $order->customer_id);
        $this->assertSame(OrderType::Delivery, $order->order_type);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(PaymentMethod::Cod, $order->payment_method);
        $this->assertEquals($expectedLine, (float) $order->subtotal_amount);
        $this->assertEquals(100.0, (float) $order->delivery_fee);
        $this->assertEquals($expectedLine + 100, (float) $order->total_amount);

        $this->assertCount(1, $order->items);
        $this->assertSame('Small pieces', $order->items->first()->preparation_type);
        $this->assertEquals(2.0, (float) $order->items->first()->quantity);

        $this->assertSame(PaymentStatus::Pending, $order->payment->payment_status);
        $this->assertEquals((float) $order->total_amount, (float) $order->payment->amount);
        $this->assertNotNull($order->deliveryTracking);
        $this->assertStringContainsString('Baneshwor', $order->address->address_line1);

        $this->assertDatabaseCount('chatbot_flow_logs', 11);
        $this->assertNotNull($this->customer->fresh()->last_ordered_at);
    }

    public function test_pickup_order_has_no_delivery_fee_or_tracking(): void
    {
        $category = Category::active()->ordered()->first();
        $product = $category->products()->active()->ordered()->first();
        $variant = $product->variants()->active()->first();

        $this->send(text: 'hi');
        $this->send(replyId: "cat:{$category->id}");
        $this->send(replyId: "prod:{$product->id}");
        $this->send(replyId: "var:{$variant->id}");
        $this->send(text: '1');
        $this->send(replyId: 'prep:normal');
        $this->send(replyId: 'cart:checkout');
        $this->send(replyId: 'fulfil:pickup');
        $this->assertSame('confirm_order', $this->session->current_step);
        $this->send(replyId: 'confirm:yes');
        $this->send(replyId: 'pay:cod');

        $order = Order::with('deliveryTracking')->firstOrFail();

        $this->assertSame(OrderType::Pickup, $order->order_type);
        $this->assertEquals(0.0, (float) $order->delivery_fee);
        $this->assertNull($order->address_id);
        $this->assertNull($order->deliveryTracking);
    }

    public function test_cancel_intent_resets_the_conversation(): void
    {
        $category = Category::active()->ordered()->first();

        $this->send(text: 'hi');
        $this->send(replyId: "cat:{$category->id}");
        $this->send(text: 'cancel');

        $this->assertSame('browse_categories', $this->session->current_step);
        $this->assertSame([], $this->session->context_data);
    }
}
