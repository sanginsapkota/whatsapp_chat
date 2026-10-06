<?php

namespace App\Services\Chatbot\Steps;

use App\Models\Category;
use App\Services\Chatbot\CatalogPresenter;
use App\Services\Chatbot\FlowStep;
use App\Services\Chatbot\StepContext;
use App\Services\Chatbot\StepResult;
use App\Services\WhatsApp\OutboundMessage;

class BrowseCategoriesStep implements FlowStep
{
    public function __construct(private readonly CatalogPresenter $presenter) {}

    public static function key(): string
    {
        return 'browse_categories';
    }

    public function handle(StepContext $context): StepResult
    {
        [$prefix, $value] = $context->reply();

        if ($prefix !== 'cat') {
            return StepResult::make(
                OutboundMessage::text('Please pick a category from the list.'),
                $this->presenter->categoryList(),
            );
        }

        $category = Category::active()->find($value);

        if (! $category) {
            return StepResult::make(
                OutboundMessage::text('That category is no longer available.'),
                $this->presenter->categoryList(),
            );
        }

        return StepResult::make($this->presenter->productList($category))
            ->to('list_products')
            ->with(['selected' => ['category_id' => $category->id]]);
    }
}
