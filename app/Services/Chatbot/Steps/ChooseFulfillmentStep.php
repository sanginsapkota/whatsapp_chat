<?php

namespace App\Services\Chatbot\Steps;

use App\Services\Chatbot\CatalogPresenter;
use App\Services\Chatbot\FlowStep;
use App\Services\Chatbot\StepContext;
use App\Services\Chatbot\StepResult;
use App\Services\WhatsApp\OutboundMessage;

class ChooseFulfillmentStep implements FlowStep
{
    public function __construct(private readonly CatalogPresenter $presenter) {}

    public static function key(): string
    {
        return 'choose_fulfillment';
    }

    public function handle(StepContext $context): StepResult
    {
        [$prefix, $choice] = $context->reply();
        $choice = $prefix === 'fulfil' ? $choice : $context->inputLower();

        if (! in_array($choice, ['delivery', 'pickup'], true)) {
            return StepResult::make(
                OutboundMessage::text('Please choose delivery or pickup.'),
                $this->presenter->fulfillmentButtons(),
            );
        }

        if ($choice === 'pickup') {
            $address = config('ordering.pickup_address');

            return StepResult::make($this->presenter->orderSummary(
                $context->cart(),
                $context->cartSubtotal(),
                0,
                'pickup',
                $address,
            ))->to('confirm_order')->with(['fulfilment' => 'pickup', 'address_id' => null, 'location' => null, 'new_address' => null]);
        }

        $customer = $context->customer();

        if ($customer && $customer->addresses()->exists()) {
            return StepResult::make($this->presenter->addressChoices($customer))
                ->to('choose_address')
                ->with(['fulfilment' => 'delivery']);
        }

        return StepResult::make(OutboundMessage::text(
            'Please share your delivery address — type it out, or send a location pin 📍.'
        ))->to('choose_address')->with(['fulfilment' => 'delivery']);
    }
}
