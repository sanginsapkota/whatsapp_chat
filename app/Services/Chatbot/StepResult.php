<?php

namespace App\Services\Chatbot;

use App\Services\WhatsApp\OutboundMessage;

/**
 * The outcome of running a single {@see FlowStep}: what to send back, where the
 * conversation moves next, and how the session context should change.
 */
class StepResult
{
    /**
     * @param  array<int, OutboundMessage>  $replies
     * @param  array<string, mixed>  $context  Merged into session context_data
     */
    public function __construct(
        public array $replies = [],
        public ?string $nextStep = null,
        public array $context = [],
        public bool $resetContext = false,
        public bool $completeSession = false,
    ) {}

    public static function make(OutboundMessage ...$replies): self
    {
        return new self(replies: $replies);
    }

    public function to(string $step): self
    {
        $this->nextStep = $step;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function with(array $context): self
    {
        $this->context = array_replace_recursive($this->context, $context);

        return $this;
    }

    public function reset(): self
    {
        $this->resetContext = true;

        return $this;
    }

    public function complete(): self
    {
        $this->completeSession = true;

        return $this;
    }
}
