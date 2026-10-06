<?php

namespace App\Filament\Resources\ChatbotMessages;

use App\Filament\Resources\ChatbotMessages\Pages\CreateChatbotMessage;
use App\Filament\Resources\ChatbotMessages\Pages\EditChatbotMessage;
use App\Filament\Resources\ChatbotMessages\Pages\ListChatbotMessages;
use App\Filament\Resources\ChatbotMessages\Schemas\ChatbotMessageForm;
use App\Filament\Resources\ChatbotMessages\Tables\ChatbotMessagesTable;
use App\Models\ChatbotMessage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ChatbotMessageResource extends Resource
{
    protected static ?string $model = ChatbotMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Chatbot';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Messages';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ChatbotMessageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChatbotMessagesTable::configure($table);
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
            'index' => ListChatbotMessages::route('/'),
            'create' => CreateChatbotMessage::route('/create'),
            'edit' => EditChatbotMessage::route('/{record}/edit'),
        ];
    }
}
