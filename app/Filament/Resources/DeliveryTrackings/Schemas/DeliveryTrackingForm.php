<?php

namespace App\Filament\Resources\DeliveryTrackings\Schemas;

use App\Enums\DeliveryStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DeliveryTrackingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('order_id')
                    ->relationship('order', 'order_number')
                    ->searchable()
                    ->required(),
                Select::make('rider_id')
                    ->label('Assigned rider')
                    ->relationship('rider', 'name', fn ($query) => $query->where('role', 'rider'))
                    ->searchable()
                    ->preload(),
                Select::make('status')
                    ->options(DeliveryStatus::class)
                    ->default('preparing')
                    ->required(),
                TextInput::make('location'),
                TextInput::make('latitude')
                    ->numeric(),
                TextInput::make('longitude')
                    ->numeric(),
                Textarea::make('note')
                    ->columnSpanFull(),
                DateTimePicker::make('assigned_at'),
                DateTimePicker::make('delivered_at'),
            ]);
    }
}
