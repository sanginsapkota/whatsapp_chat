<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryTracking extends Model
{
    protected $table = 'delivery_tracking';

    protected $fillable = [
        'order_id',
        'rider_id',
        'status',
        'location',
        'latitude',
        'longitude',
        'note',
        'assigned_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeliveryStatus::class,
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'assigned_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $tracking): void {
            if ($tracking->isDirty('rider_id') && $tracking->rider_id && $tracking->assigned_at === null) {
                $tracking->assigned_at = now();

                if ($tracking->status === DeliveryStatus::Preparing) {
                    $tracking->status = DeliveryStatus::Assigned;
                }
            }
        });
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }
}
