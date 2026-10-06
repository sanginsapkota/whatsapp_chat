<?php

namespace App\Console\Commands;

use App\Enums\SessionStatus;
use App\Models\WhatsappSession;
use Illuminate\Console\Command;

class ExpireIdleChatbotSessions extends Command
{
    protected $signature = 'chatbot:expire-sessions';

    protected $description = 'Mark active WhatsApp chatbot sessions as expired after a period of inactivity';

    public function handle(): int
    {
        $timeout = (int) config('services.whatsapp.session_timeout_minutes', 30);
        $cutoff = now()->subMinutes($timeout);

        $expired = WhatsappSession::query()
            ->where('session_status', SessionStatus::Active->value)
            ->where(function ($query) use ($cutoff) {
                $query->where('last_message_at', '<', $cutoff)
                    ->orWhereNull('last_message_at');
            })
            ->update(['session_status' => SessionStatus::Expired->value]);

        $this->info("Expired {$expired} idle session(s).");

        return self::SUCCESS;
    }
}
