<?php

namespace App\Filament\Resources\Products\RelationManagers;

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

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Cuts / variants';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('variant_name')
                    ->label('Cut / variant name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('unit')
                    ->required()
                    ->default('kg')
                    ->helperText('e.g. kg, piece, pack'),
                TextInput::make('price_per_unit')
                    ->label('Price per unit (NPR)')
                    ->required()
                    ->numeric()
                    ->minValue(0),
                TextInput::make('min_quantity')
                    ->numeric()
                    ->default(1),
                TextInput::make('step_quantity')
                    ->numeric()
                    ->default(1),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('variant_name')
            ->columns([
                TextColumn::make('variant_name')
                    ->searchable(),
                TextColumn::make('unit'),
                TextColumn::make('price_per_unit')
                    ->money('NPR')
                    ->sortable(),
                IconColumn::make('is_active')
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
