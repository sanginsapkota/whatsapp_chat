<?php

namespace App\Services\Chatbot\Steps;

use App\Services\Chatbot\CatalogPresenter;
use App\Services\Chatbot\FlowStep;
use App\Services\Chatbot\StepContext;
use App\Services\Chatbot\StepResult;
use App\Services\Orders\DeliveryFeeCalculator;
use App\Services\WhatsApp\OutboundMessage;

class ChooseAddressStep implements FlowStep
{
    public function __construct(
        private readonly CatalogPresenter $presenter,
        private readonly DeliveryFeeCalculator $fees,
    ) {}

    public static function key(): string
    {
        return 'choose_address';
    }

    public function handle(StepContext $context): StepResult
    {
        [$prefix, $value] = $context->reply();

        if ($prefix === 'addr' && $value === 'new') {
            return StepResult::make(OutboundMessage::text('Type your full delivery address (area, landmark, ward).'));
        }

        if ($prefix === 'addr' && $value === 'location') {
            return StepResult::make(OutboundMessage::text('Tap 📎 → Location → send your current location 📍.'));
        }

        if ($prefix === 'addr' && is_numeric($value)) {
            $address = $context->customer()?->addresses()->whereKey($value)->first();

            if (! $address) {
                return StepResult::make(
                    OutboundMessage::text('That address was not found.'),
                    $this->presenter->addressChoices($context->customer()),
                );
            }

            return $this->toConfirm($context, $address->singleLine(), ['address_id' => $address->id]);
        }

        if ($context->message->isLocation()) {
            return $this->toConfirm($context, 'Shared location pin 📍', [
                'address_id' => null,
                'location' => [
                    'lat' => $context->message->latitude,
                    'lng' => $context->message->longitude,
                ],
            ]);
        }

        $text = trim($context->input());

        if (mb_strlen($text) < 8) {
            return StepResult::make(OutboundMessage::text(
                'Please send a bit more detail for the address, or share a location pin 📍.'
            ));
        }

        return $this->toConfirm($context, $text, ['address_id' => null, 'new_address' => $text]);
    }

    /**
     * @param  array<string, mixed>  $context_patch
     */
    private function toConfirm(StepContext $context, string $where, array $context_patch): StepResult
    {
        $subtotal = $context->cartSubtotal();
        $fee = $this->fees->for($subtotal, 'delivery');

        return StepResult::make($this->presenter->orderSummary(
            $context->cart(),
            $subtotal,
            $fee,
            'delivery',
            $where,
        ))->to('confirm_order')->with($context_patch);
    }
}
