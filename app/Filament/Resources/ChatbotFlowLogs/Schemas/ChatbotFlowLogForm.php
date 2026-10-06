<?php

namespace App\Filament\Resources\ChatbotFlowLogs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ChatbotFlowLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('wa_id'),
                Select::make('customer_id')
                    ->relationship('customer', 'name'),
                TextInput::make('whatsapp_session_id')
                    ->numeric(),
                TextInput::make('step_key')
                    ->required(),
                TextInput::make('next_step_key'),
                TextInput::make('input_data'),
                TextInput::make('response_data'),
            ]);
    }
}
