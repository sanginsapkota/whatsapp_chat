<?php

namespace App\Filament\Resources\ChatbotMessages\Pages;

use App\Filament\Resources\ChatbotMessages\ChatbotMessageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditChatbotMessage extends EditRecord
{
    protected static string $resource = ChatbotMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
