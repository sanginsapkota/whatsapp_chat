<?php

namespace App\Services\Chatbot\Steps;

use App\Services\Chatbot\CatalogPresenter;
use App\Services\Chatbot\FlowStep;
use App\Services\Chatbot\StepContext;
use App\Services\Chatbot\StepResult;
use App\Services\WhatsApp\OutboundMessage;

class WelcomeStep implements FlowStep
{
    public function __construct(private readonly CatalogPresenter $presenter) {}

    public static function key(): string
    {
        return 'welcome';
    }

    public function handle(StepContext $context): StepResult
    {
        $name = $context->customer()?->name;
        $greeting = $name ? "Namaste {$name}! 🙏" : 'Namaste! 🙏';

        return StepResult::make(
            OutboundMessage::text("{$greeting}\n\nWelcome to *New Shuvakamana Butcher* — fresh, clean, trusted meat delivered to your door."),
            $this->presenter->categoryList(),
        )->to('browse_categories')->reset();
    }
}
