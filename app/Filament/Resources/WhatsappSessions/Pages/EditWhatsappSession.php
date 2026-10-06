<?php

namespace App\Filament\Resources\WhatsappSessions\Pages;

use App\Filament\Resources\WhatsappSessions\WhatsappSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWhatsappSession extends EditRecord
{
    protected static string $resource = WhatsappSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
