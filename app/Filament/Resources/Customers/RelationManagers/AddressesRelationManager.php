<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->placeholder('Home, Shop, ...'),
                TextInput::make('address_line1')
                    ->required()
                    ->maxLength(255),
                TextInput::make('address_line2')
                    ->maxLength(255),
                TextInput::make('city')
                    ->default('Kathmandu')
                    ->required(),
                TextInput::make('ward_no')
                    ->label('Ward no.'),
                TextInput::make('latitude')
                    ->numeric(),
                TextInput::make('longitude')
                    ->numeric(),
                Toggle::make('is_default'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('address_line1')
            ->columns([
                TextColumn::make('label')
                    ->placeholder('—'),
                TextColumn::make('address_line1')
                    ->label('Address')
                    ->formatStateUsing(fn ($record) => $record->singleLine())
                    ->wrap(),
                IconColumn::make('is_default')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
