<?php

namespace App\Services\Chatbot;

interface FlowStep
{
    /**
     * The step_key this handler is responsible for (matches
     * chatbot_flow_steps.step_key and whatsapp_sessions.current_step).
     */
    public static function key(): string;

    public function handle(StepContext $context): StepResult;
}
