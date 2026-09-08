<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappSession extends Model
{
    protected $fillable = [
        'phone_number',
        'wa_id',
        'customer_id',
        'session_status',
        'current_step',
        'context_data',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'session_status' => SessionStatus::class,
            'context_data' => 'array',
            'last_message_at' => 'datetime',
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
     * @return HasMany<ChatbotMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatbotMessage::class);
    }

    /**
     * @return HasMany<ChatbotFlowLog, $this>
     */
    public function flowLogs(): HasMany
    {
        return $this->hasMany(ChatbotFlowLog::class);
    }

    public function context(string $key, mixed $default = null): mixed
    {
        return data_get($this->context_data ?? [], $key, $default);
    }

    public function mergeContext(array $patch): void
    {
        $this->context_data = array_replace_recursive($this->context_data ?? [], $patch);
    }

    public function resetContext(): void
    {
        $this->context_data = [];
    }

    public function scopeActive($query)
    {
        return $query->where('session_status', SessionStatus::Active->value);
    }
}
