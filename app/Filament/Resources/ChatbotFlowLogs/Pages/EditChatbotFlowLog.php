<?php

namespace App\Filament\Resources\ChatbotFlowLogs\Pages;

use App\Filament\Resources\ChatbotFlowLogs\ChatbotFlowLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditChatbotFlowLog extends EditRecord
{
    protected static string $resource = ChatbotFlowLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
