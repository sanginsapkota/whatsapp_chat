<?php

namespace App\Filament\Resources\WhatsappSessions\Schemas;

use App\Enums\SessionStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WhatsappSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('phone_number')
                    ->tel()
                    ->required(),
                TextInput::make('wa_id'),
                Select::make('customer_id')
                    ->relationship('customer', 'name'),
                Select::make('session_status')
                    ->options(SessionStatus::class)
                    ->default('active')
                    ->required(),
                TextInput::make('current_step')
                    ->required()
                    ->default('welcome'),
                TextInput::make('context_data'),
                DateTimePicker::make('last_message_at'),
            ]);
    }
}
