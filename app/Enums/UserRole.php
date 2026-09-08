<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Rider = 'rider';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Rider => 'Delivery rider',
        };
    }
}
