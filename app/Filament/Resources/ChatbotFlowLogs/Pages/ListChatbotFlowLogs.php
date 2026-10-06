<?php

namespace App\Filament\Resources\ChatbotFlowLogs\Pages;

use App\Filament\Resources\ChatbotFlowLogs\ChatbotFlowLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChatbotFlowLogs extends ListRecords
{
    protected static string $resource = ChatbotFlowLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
