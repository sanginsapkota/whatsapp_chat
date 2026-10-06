<?php

namespace App\Services\Chatbot\Steps;

use App\Services\Chatbot\CatalogPresenter;
use App\Services\Chatbot\FlowStep;
use App\Services\Chatbot\StepContext;
use App\Services\Chatbot\StepResult;
use App\Services\Orders\OrderService;
use App\Services\WhatsApp\OutboundMessage;
use Illuminate\Support\Facades\Log;

class ChoosePaymentStep implements FlowStep
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly CatalogPresenter $presenter,
    ) {}

    public static function key(): string
    {
        return 'choose_payment';
    }

    public function handle(StepContext $context): StepResult
    {
        [$prefix, $method] = $context->reply();
        $method = $prefix === 'pay' ? $method : $context->inputLower();

        if (! in_array($method, ['cod', 'cash', 'cash on handover'], true)) {
            return StepResult::make(OutboundMessage::buttons(
                'Please choose a payment method.',
                [['id' => 'pay:cod', 'title' => 'Cash on handover']],
            ));
        }

        try {
            $order = $this->orders->createFromSession($context->session->fresh());
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('Order creation failed from session', [
                'session_id' => $context->session->id,
                'error' => $e->getMessage(),
            ]);

            return StepResult::make(OutboundMessage::text(
                'Sorry — we could not place the order just now. Please try again or call the shop.'
            ))->to('cart_review');
        }

        $eta = $order->order_type->value === 'pickup'
            ? "Pickup at:\n".config('ordering.pickup_address')
            : 'We will deliver to your address shortly.';

        return StepResult::make(OutboundMessage::text(
            "✅ Order *{$order->order_number}* placed!\n\n"
            .'Total: '.$this->presenter->money($order->total_amount)." (Cash on handover)\n"
            .$eta."\n\n"
            .'We will message you as the order progresses. Dhanyabaad! 🙏'
        ))->to('post_order')->complete();
    }
}
