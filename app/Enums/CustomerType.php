<?php

namespace App\Enums;

enum CustomerType: string
{
    case Regular = 'regular';
    case Vip = 'vip';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Regular',
            self::Vip => 'VIP',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
