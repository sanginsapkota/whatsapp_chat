<?php

namespace App\Services\WhatsApp;

/**
 * Serialisable description of a single outbound WhatsApp message. Produced by
 * the chatbot flow engine / notification listeners and consumed by the
 * SendWhatsAppMessage job.
 */
class OutboundMessage
{
    /**
     * @param  'text'|'buttons'|'list'|'template'  $kind
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $kind,
        public string $body,
        public array $data = [],
    ) {}

    public static function text(string $body): self
    {
        return new self('text', $body);
    }

    /**
     * @param  array<int, array{id: string, title: string}>  $buttons
     */
    public static function buttons(string $body, array $buttons, ?string $header = null, ?string $footer = null): self
    {
        return new self('buttons', $body, compact('buttons', 'header', 'footer'));
    }

    /**
     * @param  array<int, array{title: string, rows: array<int, array{id: string, title: string, description?: string}>}>  $sections
     */
    public static function list(string $body, string $button, array $sections, ?string $header = null, ?string $footer = null): self
    {
        return new self('list', $body, compact('button', 'sections', 'header', 'footer'));
    }

    /**
     * @param  array<int, mixed>  $components
     */
    public static function template(string $name, string $language = 'en', array $components = [], string $preview = ''): self
    {
        return new self('template', $preview, compact('name', 'language', 'components'));
    }

    /**
     * @return array{kind: string, body: string, data: array<string, mixed>}
     */
    public function toArray(): array
    {
        return ['kind' => $this->kind, 'body' => $this->body, 'data' => $this->data];
    }

    /**
     * @param  array{kind: string, body: string, data?: array<string, mixed>}  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self($payload['kind'], $payload['body'], $payload['data'] ?? []);
    }
}
