<?php

namespace App\Jobs;

use App\Enums\MessageDirection;
use App\Enums\MessageType;
use App\Models\ChatbotMessage;
use App\Services\WhatsApp\OutboundMessage;
use App\Services\WhatsApp\WhatsAppCloudClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    /**
     * @param  array{kind: string, body: string, data?: array<string, mixed>}  $message
     */
    public function __construct(
        public string $to,
        public array $message,
        public ?int $customerId = null,
        public ?int $sessionId = null,
    ) {}

    public function handle(WhatsAppCloudClient $client): void
    {
        $message = OutboundMessage::fromArray($this->message);

        if (! $client->isConfigured()) {
            Log::channel('whatsapp')->info('Skipping outbound send (API not configured)', [
                'to' => $this->to,
                'kind' => $message->kind,
                'body' => $message->body,
            ]);

            $this->record($message, wamId: null, status: 'skipped');

            return;
        }

        $wamId = match ($message->kind) {
            'text' => $client->sendText($this->to, $message->body),
            'buttons' => $client->sendButtons(
                $this->to,
                $message->body,
                $message->data['buttons'] ?? [],
                $message->data['header'] ?? null,
                $message->data['footer'] ?? null,
            ),
            'list' => $client->sendList(
                $this->to,
                $message->body,
                $message->data['button'] ?? 'Choose',
                $message->data['sections'] ?? [],
                $message->data['header'] ?? null,
                $message->data['footer'] ?? null,
            ),
            'template' => $client->sendTemplate(
                $this->to,
                $message->data['name'],
                $message->data['language'] ?? 'en',
                $message->data['components'] ?? [],
            ),
            default => throw new \InvalidArgumentException("Unknown message kind [{$message->kind}]."),
        };

        $this->record($message, $wamId, status: 'sent');
    }

    public function failed(Throwable $e): void
    {
        Log::channel('whatsapp')->error('Outbound WhatsApp send failed', [
            'to' => $this->to,
            'kind' => $this->message['kind'] ?? null,
            'error' => $e->getMessage(),
        ]);
    }

    private function record(OutboundMessage $message, ?string $wamId, string $status): void
    {
        ChatbotMessage::create([
            'wa_id' => $this->to,
            'wam_id' => $wamId,
            'customer_id' => $this->customerId,
            'whatsapp_session_id' => $this->sessionId,
            'message_type' => $message->kind === 'text' ? MessageType::Text : MessageType::Interactive,
            'message_content' => $message->body,
            'payload' => $message->data ?: null,
            'direction' => MessageDirection::Outbound,
            'status' => $status,
        ]);
    }
}
