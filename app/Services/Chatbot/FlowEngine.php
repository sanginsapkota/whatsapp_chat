<?php

namespace App\Services\Chatbot;

use App\Enums\SessionStatus;
use App\Models\ChatbotFlowLog;
use App\Models\WhatsappSession;
use App\Services\WhatsApp\OutboundMessage;
use Illuminate\Support\Arr;

/**
 * Runs one conversational turn: resolves the handler for the session's current
 * step (or a global intent), applies the {@see StepResult} to the session, and
 * writes a chatbot_flow_logs row.
 */
class FlowEngine
{
    public function __construct(
        private readonly StepRegistry $registry,
        private readonly CatalogPresenter $presenter,
    ) {}

    /**
     * @return array<int, OutboundMessage>
     */
    public function handle(WhatsappSession $session, InboundMessage $message): array
    {
        $context = new StepContext($session, $message);
        $originStep = $session->current_step;

        $result = $this->globalIntent($context)
            ?? $this->registry->resolve($originStep)->handle($context);

        $this->apply($session, $result);
        $this->log($session, $originStep, $message, $result);

        return $result->replies;
    }

    private function globalIntent(StepContext $context): ?StepResult
    {
        $text = $context->inputLower();
        $step = $context->session->current_step;

        return match (true) {
            in_array($text, ['cancel', 'restart', 'start over', 'stop'], true) && $step !== 'welcome' => StepResult::make(
                OutboundMessage::text('Order cancelled. Let’s start fresh.'),
                $this->presenter->categoryList(),
            )->to('browse_categories')->reset(),

            $text === 'menu' => StepResult::make($this->presenter->categoryList())->to('browse_categories'),

            in_array($text, ['help', 'info'], true) => StepResult::make(OutboundMessage::text(
                "🛒 *How to order*\n"
                ."• Pick a category, item and cut from the lists\n"
                ."• Send the quantity as a number\n"
                ."• Choose delivery or pickup, then confirm\n\n"
                .'Type *menu* to see items, *cancel* to start over, *agent* for a person.'
            )),

            in_array($text, ['agent', 'human', 'representative', 'staff'], true) => StepResult::make(OutboundMessage::text(
                'Connecting you with a team member. Type *menu* to return to automated ordering.'
            ))->to('handoff'),

            default => null,
        };
    }

    private function apply(WhatsappSession $session, StepResult $result): void
    {
        $context = $result->resetContext
            ? []
            : array_merge($session->context_data ?? [], $result->context);

        $session->context_data = $this->pruneNulls($context);
        $session->current_step = $result->nextStep ?? $session->current_step;
        $session->last_message_at = now();

        if ($result->completeSession) {
            $session->session_status = SessionStatus::Completed;
        }

        $session->save();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function pruneNulls(array $data): array
    {
        return array_filter($data, fn ($value) => $value !== null);
    }

    private function log(WhatsappSession $session, string $originStep, InboundMessage $message, StepResult $result): void
    {
        ChatbotFlowLog::create([
            'wa_id' => $session->phone_number,
            'customer_id' => $session->customer_id,
            'whatsapp_session_id' => $session->id,
            'step_key' => $originStep,
            'next_step_key' => $session->current_step,
            'input_data' => [
                'type' => $message->type,
                'value' => $message->value(),
            ],
            'response_data' => [
                'replies' => array_map(
                    fn (OutboundMessage $m) => Arr::only($m->toArray(), ['kind', 'body']),
                    $result->replies,
                ),
                'context' => $session->context_data,
            ],
        ]);
    }
}
