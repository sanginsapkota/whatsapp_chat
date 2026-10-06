<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Arr;

/**
 * Normalised view of one inbound WhatsApp message, flattened from the Meta
 * webhook payload so flow-step handlers never touch the raw envelope.
 */
class InboundMessage
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $wamId,
        public string $from,
        public string $type,
        public ?string $text = null,
        public ?string $replyId = null,
        public ?string $replyTitle = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $profileName = null,
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $message
     * @param  array<int, array<string, mixed>>  $contacts
     */
    public static function fromWebhook(array $message, array $contacts = []): self
    {
        $type = (string) Arr::get($message, 'type', 'unknown');
        $interactive = Arr::get($message, 'interactive', []);
        $interactiveType = Arr::get($interactive, 'type');

        return new self(
            wamId: (string) Arr::get($message, 'id', ''),
            from: (string) Arr::get($message, 'from', ''),
            type: $type,
            text: match ($type) {
                'text' => Arr::get($message, 'text.body'),
                'button' => Arr::get($message, 'button.text'),
                default => null,
            },
            replyId: $interactiveType ? Arr::get($interactive, "{$interactiveType}.id") : Arr::get($message, 'button.payload'),
            replyTitle: $interactiveType ? Arr::get($interactive, "{$interactiveType}.title") : null,
            latitude: Arr::get($message, 'location.latitude'),
            longitude: Arr::get($message, 'location.longitude'),
            profileName: Arr::get($contacts, '0.profile.name'),
            raw: $message,
        );
    }

    /**
     * Best-effort "what did the user say" string for logging and free-text steps.
     */
    public function value(): string
    {
        return trim((string) ($this->replyId ?? $this->replyTitle ?? $this->text ?? ''));
    }

    public function isLocation(): bool
    {
        return $this->type === 'location' && $this->latitude !== null;
    }
}
