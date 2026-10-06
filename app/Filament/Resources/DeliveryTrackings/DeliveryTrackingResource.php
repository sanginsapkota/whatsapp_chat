<?php

namespace App\Filament\Resources\DeliveryTrackings;

use App\Filament\Resources\DeliveryTrackings\Pages\CreateDeliveryTracking;
use App\Filament\Resources\DeliveryTrackings\Pages\EditDeliveryTracking;
use App\Filament\Resources\DeliveryTrackings\Pages\ListDeliveryTrackings;
use App\Filament\Resources\DeliveryTrackings\Schemas\DeliveryTrackingForm;
use App\Filament\Resources\DeliveryTrackings\Tables\DeliveryTrackingsTable;
use App\Models\DeliveryTracking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DeliveryTrackingResource extends Resource
{
    protected static ?string $model = DeliveryTracking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Deliveries';

    public static function form(Schema $schema): Schema
    {
        return DeliveryTrackingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeliveryTrackingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeliveryTrackings::route('/'),
            'create' => CreateDeliveryTracking::route('/create'),
            'edit' => EditDeliveryTracking::route('/{record}/edit'),
        ];
    }
}
