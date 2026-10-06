<?php

namespace App\Services\Chatbot\Steps;

use App\Models\ProductVariant;
use App\Services\Chatbot\CatalogPresenter;
use App\Services\Chatbot\FlowStep;
use App\Services\Chatbot\StepContext;
use App\Services\Chatbot\StepResult;
use App\Services\WhatsApp\OutboundMessage;

class SelectPreparationStep implements FlowStep
{
    public function __construct(private readonly CatalogPresenter $presenter) {}

    public static function key(): string
    {
        return 'select_preparation';
    }

    public function handle(StepContext $context): StepResult
    {
        $selected = $context->get('selected', []);
        $variant = ProductVariant::with('product')->find($selected['variant_id'] ?? null);

        if (! $variant || ! isset($selected['quantity'])) {
            return StepResult::make(OutboundMessage::text("Let's start again."))->to('welcome');
        }

        [$prefix, $code] = $context->reply();
        $options = config('ordering.preparation_options');

        $preparation = $prefix === 'prep' && isset($options[$code])
            ? $options[$code]
            : ($context->input() ?: 'Normal cut');

        $item = [
            'variant_id' => $variant->id,
            'product_name' => $variant->product->name,
            'variant_name' => $variant->variant_name,
            'unit' => $variant->unit,
            'unit_price' => (float) $variant->price_per_unit,
            'quantity' => (float) $selected['quantity'],
            'line_total' => (float) $selected['line_total'],
            'preparation' => $preparation,
        ];

        $cart = $context->cart();
        $cart[] = $item;
        $subtotal = (float) collect($cart)->sum('line_total');

        return StepResult::make(
            OutboundMessage::text("Added ✅ — {$item['product_name']} ({$preparation})."),
            $this->presenter->cartSummary($cart, $subtotal),
        )->to('cart_review')->with(['cart' => $cart, 'selected' => null]);
    }
}
