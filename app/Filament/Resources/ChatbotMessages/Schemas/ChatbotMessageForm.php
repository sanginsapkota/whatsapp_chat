<?php

namespace App\Filament\Resources\ChatbotMessages\Schemas;

use App\Enums\MessageDirection;
use App\Enums\MessageType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ChatbotMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('wa_id'),
                TextInput::make('wam_id'),
                Select::make('customer_id')
                    ->relationship('customer', 'name'),
                TextInput::make('whatsapp_session_id')
                    ->numeric(),
                Select::make('message_type')
                    ->options(MessageType::class)
                    ->default('text')
                    ->required(),
                Textarea::make('message_content')
                    ->columnSpanFull(),
                TextInput::make('payload'),
                Select::make('direction')
                    ->options(MessageDirection::class)
                    ->required(),
                TextInput::make('status'),
            ]);
    }
}
