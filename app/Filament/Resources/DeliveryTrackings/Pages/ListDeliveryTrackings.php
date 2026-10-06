<?php

namespace App\Filament\Resources\DeliveryTrackings\Pages;

use App\Filament\Resources\DeliveryTrackings\DeliveryTrackingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDeliveryTrackings extends ListRecords
{
    protected static string $resource = DeliveryTrackingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
