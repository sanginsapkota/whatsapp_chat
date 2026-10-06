<?php

namespace App\Services\Chatbot\Steps;

use App\Models\Category;
use App\Models\Product;
use App\Services\Chatbot\CatalogPresenter;
use App\Services\Chatbot\FlowStep;
use App\Services\Chatbot\StepContext;
use App\Services\Chatbot\StepResult;
use App\Services\WhatsApp\OutboundMessage;

class ListProductsStep implements FlowStep
{
    public function __construct(private readonly CatalogPresenter $presenter) {}

    public static function key(): string
    {
        return 'list_products';
    }

    public function handle(StepContext $context): StepResult
    {
        [$prefix, $value] = $context->reply();

        if ($prefix === 'cat') {
            return app(BrowseCategoriesStep::class)->handle($context);
        }

        $categoryId = $context->get('selected.category_id');
        $category = $categoryId ? Category::find($categoryId) : null;

        if ($prefix !== 'prod') {
            $retry = $category
                ? $this->presenter->productList($category)
                : $this->presenter->categoryList();

            return StepResult::make(OutboundMessage::text('Please choose an item from the list.'), $retry);
        }

        $product = Product::active()->with('variants')->find($value);

        if (! $product || $product->variants->isEmpty()) {
            $retry = $category ? $this->presenter->productList($category) : $this->presenter->categoryList();

            return StepResult::make(OutboundMessage::text('That item is unavailable right now.'), $retry);
        }

        return StepResult::make($this->presenter->variantList($product))
            ->to('select_variant')
            ->with(['selected' => array_merge($context->get('selected', []), ['product_id' => $product->id])]);
    }
}
