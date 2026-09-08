<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotFlowLog extends Model
{
    protected $fillable = [
        'wa_id',
        'customer_id',
        'whatsapp_session_id',
        'step_key',
        'next_step_key',
        'input_data',
        'response_data',
    ];

    protected function casts(): array
    {
        return [
            'input_data' => 'array',
            'response_data' => 'array',
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
