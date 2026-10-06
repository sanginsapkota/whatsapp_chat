<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Order items';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                TextColumn::make('product_name')
                    ->label('Product')
                    ->description(fn ($record) => $record->variant_name)
                    ->searchable(),
                TextColumn::make('preparation_type')
                    ->label('Preparation')
                    ->placeholder('—'),
                TextColumn::make('quantity')
                    ->formatStateUsing(fn ($state, $record) => rtrim(rtrim((string) $state, '0'), '.').' '.$record->unit),
                TextColumn::make('unit_price')
                    ->label('Rate')
                    ->money('NPR'),
                TextColumn::make('line_total')
                    ->label('Total')
                    ->money('NPR'),
            ]);
    }
}
