<?php

namespace Tests\Feature;

use Tests\TestCase;

class WhatsAppWebhookVerificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.whatsapp.verify_token', 'secret-verify-token');
        config()->set('services.whatsapp.app_secret', null);
    }

    public function test_it_echoes_the_challenge_when_the_verify_token_matches(): void
    {
        $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=secret-verify-token&hub.challenge=1234567')
            ->assertOk()
            ->assertSee('1234567', escape: false);
    }

    public function test_it_rejects_a_wrong_verify_token(): void
    {
        $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=nope&hub.challenge=1234567')
            ->assertForbidden();
    }
}
