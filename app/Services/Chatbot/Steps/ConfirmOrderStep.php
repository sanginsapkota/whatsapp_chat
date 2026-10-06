<?php

namespace App\Services\Chatbot\Steps;

use App\Services\Chatbot\CatalogPresenter;
use App\Services\Chatbot\FlowStep;
use App\Services\Chatbot\StepContext;
use App\Services\Chatbot\StepResult;
use App\Services\WhatsApp\OutboundMessage;

class ConfirmOrderStep implements FlowStep
{
    public function __construct(private readonly CatalogPresenter $presenter) {}

    public static function key(): string
    {
        return 'confirm_order';
    }

    public function handle(StepContext $context): StepResult
    {
        [$prefix, $choice] = $context->reply();
        $choice = $prefix === 'confirm' ? $choice : $context->inputLower();

        if (in_array($choice, ['no', 'back', 'back to cart', 'cancel'], true)) {
            return StepResult::make(
                OutboundMessage::text('No problem — back to your cart.'),
                $this->presenter->cartSummary($context->cart(), $context->cartSubtotal()),
            )->to('cart_review');
        }

        if (! in_array($choice, ['yes', 'confirm', 'confirm order'], true)) {
            return StepResult::make(OutboundMessage::buttons(
                'Please confirm to place the order, or go back to the cart.',
                [
                    ['id' => 'confirm:yes', 'title' => 'Confirm order'],
                    ['id' => 'confirm:no', 'title' => 'Back to cart'],
                ],
            ));
        }

        return StepResult::make(OutboundMessage::buttons(
            'How would you like to pay?',
            [['id' => 'pay:cod', 'title' => 'Cash on handover']],
            footer: 'Online payment (Khalti / eSewa) coming soon.',
        ))->to('choose_payment');
    }
}
