<?php

namespace App\Jobs;

use App\Enums\MessageDirection;
use App\Enums\MessageType;
use App\Enums\SessionStatus;
use App\Models\ChatbotMessage;
use App\Models\Customer;
use App\Models\WhatsappSession;
use App\Services\Chatbot\FlowEngine;
use App\Services\Chatbot\InboundMessage;
use App\Services\WhatsApp\OutboundMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessInboundWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 15;

    /**
     * @param  array<string, mixed>  $message  Raw Meta message object
     * @param  array<int, array<string, mixed>>  $contacts
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public array $message,
        public array $contacts = [],
        public array $metadata = [],
    ) {}

    public function handle(FlowEngine $engine): void
    {
        $inbound = InboundMessage::fromWebhook($this->message, $this->contacts);

        if ($inbound->wamId === '' || $inbound->from === '') {
            return;
        }

        // Idempotency — Meta re-delivers on any non-200 / timeout.
        if (ChatbotMessage::where('wam_id', $inbound->wamId)->exists()) {
            return;
        }

        [$customer, $session] = DB::transaction(function () use ($inbound) {
            $customer = $this->resolveCustomer($inbound);
            $session = $this->resolveSession($inbound, $customer);

            ChatbotMessage::create([
                'wa_id' => $inbound->from,
                'wam_id' => $inbound->wamId,
                'customer_id' => $customer->id,
                'whatsapp_session_id' => $session->id,
                'message_type' => $this->messageType($inbound->type),
                'message_content' => $inbound->value() ?: null,
                'payload' => $inbound->raw ?: null,
                'direction' => MessageDirection::Inbound,
                'status' => 'received',
            ]);

            return [$customer, $session];
        });

        try {
            $replies = $engine->handle($session->fresh(), $inbound);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('Flow engine error', [
                'session_id' => $session->id,
                'wam_id' => $inbound->wamId,
                'error' => $e->getMessage(),
            ]);

            SendWhatsAppMessage::dispatch(
                $inbound->from,
                OutboundMessage::text('Something went wrong on our side. Please try again in a moment.')->toArray(),
                $customer->id,
                $session->id,
            );

            throw $e;
        }

        foreach ($replies as $reply) {
            SendWhatsAppMessage::dispatch($inbound->from, $reply->toArray(), $customer->id, $session->id);
        }
    }

    private function resolveCustomer(InboundMessage $inbound): Customer
    {
        $customer = Customer::firstOrCreate(
            ['phone_number' => $inbound->from],
            ['name' => $inbound->profileName],
        );

        if (blank($customer->name) && filled($inbound->profileName)) {
            $customer->update(['name' => $inbound->profileName]);
        }

        return $customer;
    }

    private function resolveSession(InboundMessage $inbound, Customer $customer): WhatsappSession
    {
        $timeout = (int) config('services.whatsapp.session_timeout_minutes', 30);

        $session = WhatsappSession::where('phone_number', $inbound->from)
            ->where('session_status', SessionStatus::Active->value)
            ->where('last_message_at', '>=', now()->subMinutes($timeout))
            ->latest()
            ->first();

        if ($session) {
            $session->update([
                'customer_id' => $customer->id,
                'wa_id' => $inbound->wamId,
                'last_message_at' => now(),
            ]);

            return $session;
        }

        return WhatsappSession::create([
            'phone_number' => $inbound->from,
            'wa_id' => $inbound->wamId,
            'customer_id' => $customer->id,
            'session_status' => SessionStatus::Active,
            'current_step' => 'welcome',
            'context_data' => [],
            'last_message_at' => now(),
        ]);
    }

    private function messageType(string $type): MessageType
    {
        return match ($type) {
            'text' => MessageType::Text,
            'image' => MessageType::Image,
            'button' => MessageType::Button,
            'interactive' => MessageType::Interactive,
            'location' => MessageType::Location,
            default => MessageType::Text,
        };
    }
}
