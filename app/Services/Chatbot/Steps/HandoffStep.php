<?php

namespace App\Services\Chatbot\Steps;

use App\Services\Chatbot\FlowStep;
use App\Services\Chatbot\StepContext;
use App\Services\Chatbot\StepResult;
use App\Services\WhatsApp\OutboundMessage;

class HandoffStep implements FlowStep
{
    public static function key(): string
    {
        return 'handoff';
    }

    public function handle(StepContext $context): StepResult
    {
        if ($context->inputLower() === 'menu' || $context->inputLower() === 'bot') {
            return StepResult::make(OutboundMessage::text('Back to the ordering assistant.'))
                ->to('welcome')
                ->reset();
        }

        return StepResult::make(OutboundMessage::text(
            'A team member has been notified and will reply here shortly. '
            .'Send "menu" any time to go back to automated ordering.'
        ));
    }
}
