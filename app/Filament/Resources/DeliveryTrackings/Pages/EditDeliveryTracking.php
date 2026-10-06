<?php

namespace App\Filament\Resources\DeliveryTrackings\Pages;

use App\Filament\Resources\DeliveryTrackings\DeliveryTrackingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryTracking extends EditRecord
{
    protected static string $resource = DeliveryTrackingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
