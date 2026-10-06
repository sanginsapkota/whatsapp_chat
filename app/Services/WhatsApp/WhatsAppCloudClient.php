<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around the Meta WhatsApp Cloud API graph endpoints.
 *
 * Every send returns the Meta message id ("wamid...") so callers can persist it
 * against a chatbot_messages row and later reconcile delivery receipts.
 */
class WhatsAppCloudClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.whatsapp.phone_number_id'))
            && filled(config('services.whatsapp.access_token'));
    }

    public function sendText(string $to, string $body, bool $previewUrl = false): string
    {
        return $this->send($to, [
            'type' => 'text',
            'text' => ['body' => $body, 'preview_url' => $previewUrl],
        ]);
    }

    /**
     * @param  array<int, array{id: string, title: string}>  $buttons
     */
    public function sendButtons(string $to, string $body, array $buttons, ?string $header = null, ?string $footer = null): string
    {
        $action = [
            'buttons' => array_map(fn (array $b) => [
                'type' => 'reply',
                'reply' => ['id' => $b['id'], 'title' => mb_substr($b['title'], 0, 20)],
            ], $buttons),
        ];

        return $this->send($to, [
            'type' => 'interactive',
            'interactive' => array_filter([
                'type' => 'button',
                'header' => $header ? ['type' => 'text', 'text' => $header] : null,
                'body' => ['text' => $body],
                'footer' => $footer ? ['text' => $footer] : null,
                'action' => $action,
            ]),
        ]);
    }

    /**
     * @param  array<int, array{title: string, rows: array<int, array{id: string, title: string, description?: string}>}>  $sections
     */
    public function sendList(string $to, string $body, string $buttonText, array $sections, ?string $header = null, ?string $footer = null): string
    {
        return $this->send($to, [
            'type' => 'interactive',
            'interactive' => array_filter([
                'type' => 'list',
                'header' => $header ? ['type' => 'text', 'text' => $header] : null,
                'body' => ['text' => $body],
                'footer' => $footer ? ['text' => $footer] : null,
                'action' => [
                    'button' => mb_substr($buttonText, 0, 20),
                    'sections' => $sections,
                ],
            ]),
        ]);
    }

    /**
     * @param  array<int, mixed>  $components
     */
    public function sendTemplate(string $to, string $template, string $language = 'en', array $components = []): string
    {
        return $this->send($to, [
            'type' => 'template',
            'template' => array_filter([
                'name' => $template,
                'language' => ['code' => $language],
                'components' => $components ?: null,
            ]),
        ]);
    }

    public function markRead(string $wamId): void
    {
        $this->request()->post($this->messagesUrl(), [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $wamId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    protected function send(string $to, array $message): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('WhatsApp Cloud API is not configured.');
        }

        $response = $this->request()->post($this->messagesUrl(), array_merge([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
        ], $message));

        $this->throwOnError($response);

        return (string) $response->json('messages.0.id', '');
    }

    protected function request(): PendingRequest
    {
        return Http::withToken(config('services.whatsapp.access_token'))
            ->acceptJson()
            ->timeout(15)
            ->retry(2, 200, throw: false);
    }

    protected function messagesUrl(): string
    {
        return sprintf(
            '%s/%s/%s/messages',
            rtrim((string) config('services.whatsapp.graph_base_url'), '/'),
            config('services.whatsapp.api_version'),
            config('services.whatsapp.phone_number_id'),
        );
    }

    protected function throwOnError(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        throw new RuntimeException(sprintf(
            'WhatsApp send failed (%d): %s',
            $response->status(),
            $response->json('error.message') ?? $response->body(),
        ));
    }
}
