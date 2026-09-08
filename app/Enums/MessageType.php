<?php

namespace App\Enums;

enum MessageType: string
{
    case Text = 'text';
    case Image = 'image';
    case Button = 'button';
    case Interactive = 'interactive';
    case Location = 'location';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
