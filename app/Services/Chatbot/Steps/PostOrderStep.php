<?php

namespace App\Services\Chatbot\Steps;

use App\Services\Chatbot\CatalogPresenter;
use App\Services\Chatbot\FlowStep;
use App\Services\Chatbot\StepContext;
use App\Services\Chatbot\StepResult;
use App\Services\WhatsApp\OutboundMessage;

class PostOrderStep implements FlowStep
{
    public function __construct(private readonly CatalogPresenter $presenter) {}

    public static function key(): string
    {
        return 'post_order';
    }

    public function handle(StepContext $context): StepResult
    {
        return StepResult::make(
            OutboundMessage::text('Would you like to place another order?'),
            $this->presenter->categoryList(),
        )->to('browse_categories')->reset();
    }
}
