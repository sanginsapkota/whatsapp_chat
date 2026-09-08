<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case Preparing = 'preparing';
    case Assigned = 'assigned';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';

    public function label(): string
    {
        return match ($this) {
            self::Preparing => 'Preparing',
            self::Assigned => 'Assigned to rider',
            self::OutForDelivery => 'Out for delivery',
            self::Delivered => 'Delivered',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Preparing => 'warning',
            self::Assigned => 'info',
            self::OutForDelivery => 'primary',
            self::Delivered => 'success',
        };
    }
}
