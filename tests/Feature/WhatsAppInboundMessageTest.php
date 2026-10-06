<?php

namespace Tests\Feature;

use App\Enums\MessageDirection;
use App\Jobs\ProcessInboundWhatsAppMessage;
use App\Jobs\SendWhatsAppMessage;
use App\Models\ChatbotMessage;
use App\Models\Customer;
use App\Models\WhatsappSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class WhatsAppInboundMessageTest extends TestCase
{
    use RefreshDatabase;

    private function payload(string $text = 'hi', string $wamId = 'wamid.TEST1'): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => 'WABA123',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => ['display_phone_number' => '15550000000', 'phone_number_id' => 'PNID123'],
                        'contacts' => [['profile' => ['name' => 'Sita Sharma'], 'wa_id' => '9779812345678']],
                        'messages' => [[
                            'from' => '9779812345678',
                            'id' => $wamId,
                            'timestamp' => (string) now()->timestamp,
                            'type' => 'text',
                            'text' => ['body' => $text],
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    public function test_webhook_queues_an_inbound_processing_job(): void
    {
        Bus::fake();

        $this->postJson('/webhooks/whatsapp', $this->payload())->assertOk();

        Bus::assertDispatched(ProcessInboundWhatsAppMessage::class);
    }

    public function test_processing_job_logs_the_message_and_creates_a_session(): void
    {
        Bus::fake([SendWhatsAppMessage::class]);

        $this->postJson('/webhooks/whatsapp', $this->payload('hello'))->assertOk();

        $this->assertDatabaseHas('customers', [
            'phone_number' => '9779812345678',
            'name' => 'Sita Sharma',
        ]);

        $session = WhatsappSession::firstOrFail();
        $this->assertSame('9779812345678', $session->phone_number);
        // The welcome turn immediately advances the conversation to category browsing.
        $this->assertSame('browse_categories', $session->current_step);

        $inbound = ChatbotMessage::where('direction', MessageDirection::Inbound)->firstOrFail();
        $this->assertSame('wamid.TEST1', $inbound->wam_id);
        $this->assertSame('hello', $inbound->message_content);

        Bus::assertDispatched(SendWhatsAppMessage::class);
    }

    public function test_duplicate_delivery_is_ignored(): void
    {
        Bus::fake([SendWhatsAppMessage::class]);

        $this->postJson('/webhooks/whatsapp', $this->payload('once', 'wamid.DUP'))->assertOk();
        $this->postJson('/webhooks/whatsapp', $this->payload('once', 'wamid.DUP'))->assertOk();

        $this->assertSame(1, ChatbotMessage::where('direction', MessageDirection::Inbound)->count());
        $this->assertSame(1, Customer::count());
    }
}
