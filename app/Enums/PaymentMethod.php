<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Esewa = 'esewa';
    case Khalti = 'khalti';
    case Fonepay = 'fonepay';
    case Cod = 'cod';

    public function label(): string
    {
        return match ($this) {
            self::Esewa => 'eSewa',
            self::Khalti => 'Khalti',
            self::Fonepay => 'Fonepay',
            self::Cod => 'Cash on delivery / pickup',
        };
    }

    /**
     * Payment methods currently available to customers in the chatbot.
     *
     * @return array<int, self>
     */
    public static function enabled(): array
    {
        return [self::Cod];
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
