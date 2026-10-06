<?php

namespace App\Services\Chatbot\Steps;

use App\Models\ProductVariant;
use App\Services\Chatbot\CatalogPresenter;
use App\Services\Chatbot\FlowStep;
use App\Services\Chatbot\StepContext;
use App\Services\Chatbot\StepResult;
use App\Services\WhatsApp\OutboundMessage;

class EnterQuantityStep implements FlowStep
{
    public function __construct(private readonly CatalogPresenter $presenter) {}

    public static function key(): string
    {
        return 'enter_quantity';
    }

    public function handle(StepContext $context): StepResult
    {
        $variant = ProductVariant::active()->with('product')->find($context->get('selected.variant_id'));

        if (! $variant) {
            return StepResult::make(OutboundMessage::text("Let's start again."))->to('welcome');
        }

        $raw = str_replace(',', '.', trim($context->input()));
        preg_match('/-?\d+(\.\d+)?/', $raw, $m);
        $quantity = isset($m[0]) ? (float) $m[0] : 0.0;

        $min = (float) $variant->min_quantity;
        $stepSize = max((float) $variant->step_quantity, 0.01);

        if ($quantity < $min) {
            return StepResult::make(OutboundMessage::text(
                "Please enter at least {$this->presenter->qty($min)} {$variant->unit}."
            ));
        }

        // Snap to the nearest valid step.
        $steps = max(1, (int) round(($quantity - $min) / $stepSize));
        $quantity = round($min + $steps * $stepSize, 2);

        $lineTotal = round($quantity * (float) $variant->price_per_unit, 2);

        return StepResult::make(
            OutboundMessage::text(
                "Got it — {$this->presenter->qty($quantity)} {$variant->unit} of {$variant->product->name} ({$variant->variant_name}) = {$this->presenter->money($lineTotal)}."
            ),
            $this->presenter->preparationButtons(),
        )->to('select_preparation')->with(['selected' => array_merge($context->get('selected', []), [
            'quantity' => $quantity,
            'line_total' => $lineTotal,
        ])]);
    }
}
