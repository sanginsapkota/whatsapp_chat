<?php

namespace App\Filament\Resources\ChatbotMessages\Pages;

use App\Filament\Resources\ChatbotMessages\ChatbotMessageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChatbotMessages extends ListRecords
{
    protected static string $resource = ChatbotMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
