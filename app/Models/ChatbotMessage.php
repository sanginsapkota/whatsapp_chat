<?php

namespace App\Models;

use App\Enums\MessageDirection;
use App\Enums\MessageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotMessage extends Model
{
    protected $fillable = [
        'wa_id',
        'wam_id',
        'customer_id',
        'whatsapp_session_id',
        'message_type',
        'message_content',
        'payload',
        'direction',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'message_type' => MessageType::class,
            'direction' => MessageDirection::class,
            'payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<WhatsappSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(WhatsappSession::class, 'whatsapp_session_id');
    }
}
