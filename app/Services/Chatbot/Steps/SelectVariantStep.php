<?php

namespace App\Services\Chatbot\Steps;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Chatbot\CatalogPresenter;
use App\Services\Chatbot\FlowStep;
use App\Services\Chatbot\StepContext;
use App\Services\Chatbot\StepResult;
use App\Services\WhatsApp\OutboundMessage;

class SelectVariantStep implements FlowStep
{
    public function __construct(private readonly CatalogPresenter $presenter) {}

    public static function key(): string
    {
        return 'select_variant';
    }

    public function handle(StepContext $context): StepResult
    {
        [$prefix, $value] = $context->reply();

        if ($prefix === 'prod') {
            return app(ListProductsStep::class)->handle($context);
        }

        $productId = $context->get('selected.product_id');

        if ($prefix !== 'var') {
            $product = $productId ? Product::find($productId) : null;

            return StepResult::make(
                OutboundMessage::text('Please choose a cut from the list.'),
                $product ? $this->presenter->variantList($product) : $this->presenter->categoryList(),
            );
        }

        $variant = ProductVariant::active()->with('product')->find($value);

        if (! $variant) {
            $product = $productId ? Product::find($productId) : null;

            return StepResult::make(
                OutboundMessage::text('That cut is unavailable.'),
                $product ? $this->presenter->variantList($product) : $this->presenter->categoryList(),
            );
        }

        $min = $this->presenter->qty($variant->min_quantity);
        $step = $this->presenter->qty($variant->step_quantity);

        return StepResult::make(OutboundMessage::text(
            "*{$variant->product->name} — {$variant->variant_name}*\n"
            .$this->presenter->money($variant->price_per_unit)." per {$variant->unit}\n\n"
            ."How much do you need? Reply with a number in *{$variant->unit}* (min {$min}, steps of {$step})."
        ))->to('enter_quantity')->with(['selected' => array_merge($context->get('selected', []), ['variant_id' => $variant->id])]);
    }
}
