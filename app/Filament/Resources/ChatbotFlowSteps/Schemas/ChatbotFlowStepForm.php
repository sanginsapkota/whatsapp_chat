<?php

namespace App\Filament\Resources\ChatbotFlowSteps\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ChatbotFlowStepForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('step_key')
                    ->required(),
                TextInput::make('step_name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('next_steps'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
