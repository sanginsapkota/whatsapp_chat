<?php

namespace App\Services\Chatbot;

use App\Services\Chatbot\Steps\BrowseCategoriesStep;
use App\Services\Chatbot\Steps\CartReviewStep;
use App\Services\Chatbot\Steps\ChooseAddressStep;
use App\Services\Chatbot\Steps\ChooseFulfillmentStep;
use App\Services\Chatbot\Steps\ChoosePaymentStep;
use App\Services\Chatbot\Steps\ConfirmOrderStep;
use App\Services\Chatbot\Steps\EnterQuantityStep;
use App\Services\Chatbot\Steps\HandoffStep;
use App\Services\Chatbot\Steps\ListProductsStep;
use App\Services\Chatbot\Steps\PostOrderStep;
use App\Services\Chatbot\Steps\SelectPreparationStep;
use App\Services\Chatbot\Steps\SelectVariantStep;
use App\Services\Chatbot\Steps\WelcomeStep;

class StepRegistry
{
    /**
     * @var array<int, class-string<FlowStep>>
     */
    private const STEPS = [
        WelcomeStep::class,
        BrowseCategoriesStep::class,
        ListProductsStep::class,
        SelectVariantStep::class,
        EnterQuantityStep::class,
        SelectPreparationStep::class,
        CartReviewStep::class,
        ChooseFulfillmentStep::class,
        ChooseAddressStep::class,
        ConfirmOrderStep::class,
        ChoosePaymentStep::class,
        PostOrderStep::class,
        HandoffStep::class,
    ];

    public function resolve(string $key): FlowStep
    {
        return app($this->map()[$key] ?? WelcomeStep::class);
    }

    public function has(string $key): bool
    {
        return isset($this->map()[$key]);
    }

    /**
     * @return array<string, class-string<FlowStep>>
     */
    private function map(): array
    {
        static $map = null;

        return $map ??= collect(self::STEPS)
            ->keyBy(fn (string $class) => $class::key())
            ->all();
    }
}
