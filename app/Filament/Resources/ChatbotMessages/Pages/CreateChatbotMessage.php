<?php

namespace App\Filament\Resources\ChatbotMessages\Pages;

use App\Filament\Resources\ChatbotMessages\ChatbotMessageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateChatbotMessage extends CreateRecord
{
    protected static string $resource = ChatbotMessageResource::class;
}
