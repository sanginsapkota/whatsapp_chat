<?php

namespace App\Enums;

enum SessionStatus: string
{
    case Active = 'active';
    case Idle = 'idle';
    case Expired = 'expired';
    case Completed = 'completed';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Idle => 'warning',
            self::Expired => 'gray',
            self::Completed => 'info',
        };
    }
}
