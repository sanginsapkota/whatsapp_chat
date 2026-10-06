<?php

namespace App\Services\Chatbot;

use App\Models\Customer;
use App\Models\WhatsappSession;
use Illuminate\Support\Arr;

/**
 * Everything a flow step needs about the current turn. Steps read the inbound
 * message and the session context; they never mutate the session directly —
 * changes flow back through {@see StepResult}.
 */
class StepContext
{
    public function __construct(
        public readonly WhatsappSession $session,
        public readonly InboundMessage $message,
    ) {}

    public function customer(): ?Customer
    {
        return $this->session->customer;
    }

    /**
     * Raw text / interactive-reply value the customer sent.
     */
    public function input(): string
    {
        return $this->message->value();
    }

    public function inputLower(): string
    {
        return mb_strtolower($this->input());
    }

    /**
     * Interactive reply id (e.g. "cat:3", "prep:small"), split into [prefix, value].
     *
     * @return array{0: string, 1: string}
     */
    public function reply(): array
    {
        $id = $this->message->replyId ?? '';

        if (! str_contains($id, ':')) {
            return [$id, ''];
        }

        [$prefix, $value] = explode(':', $id, 2);

        return [$prefix, $value];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->session->context_data ?? [], $key, $default);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function cart(): array
    {
        return $this->get('cart', []);
    }

    public function cartCount(): int
    {
        return count($this->cart());
    }

    public function cartSubtotal(): float
    {
        return (float) collect($this->cart())->sum('line_total');
    }
}
