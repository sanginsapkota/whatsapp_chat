<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInboundWhatsAppMessage;
use App\Models\ChatbotMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Endpoint verification handshake (Meta calls this once when the webhook
     * URL is saved in the app dashboard).
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

        $expected = config('services.whatsapp.verify_token');

        if ($mode === 'subscribe' && filled($expected) && hash_equals((string) $expected, (string) $token)) {
            return response((string) $challenge, Response::HTTP_OK)
                ->header('Content-Type', 'text/plain');
        }

        return response('Verification failed.', Response::HTTP_FORBIDDEN);
    }

    /**
     * Inbound messages and status callbacks. Meta expects a fast 200; all real
     * work is pushed onto the queue.
     */
    public function receive(Request $request): Response
    {
        $payload = $request->json()->all();

        foreach (Arr::get($payload, 'entry', []) as $entry) {
            foreach (Arr::get($entry, 'changes', []) as $change) {
                $value = Arr::get($change, 'value', []);
                $metadata = Arr::get($value, 'metadata', []);
                $contacts = Arr::get($value, 'contacts', []);

                foreach (Arr::get($value, 'messages', []) as $message) {
                    ProcessInboundWhatsAppMessage::dispatch($message, $contacts, $metadata);
                }

                $this->recordStatuses(Arr::get($value, 'statuses', []));
            }
        }

        return response('', Response::HTTP_OK);
    }

    /**
     * Update outbound message rows with delivery receipts (sent / delivered /
     * read / failed).
     *
     * @param  array<int, array<string, mixed>>  $statuses
     */
    private function recordStatuses(array $statuses): void
    {
        foreach ($statuses as $status) {
            $wamId = Arr::get($status, 'id');
            $state = Arr::get($status, 'status');

            if (blank($wamId) || blank($state)) {
                continue;
            }

            $updated = ChatbotMessage::where('wam_id', $wamId)->update(['status' => $state]);

            if ($updated === 0 && $state === 'failed') {
                Log::channel('whatsapp')->warning('Delivery failure for unknown message', $status);
            }
        }
    }
}
