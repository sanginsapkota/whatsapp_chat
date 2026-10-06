<?php

namespace App\Filament\Resources\ChatbotFlowLogs;

use App\Filament\Resources\ChatbotFlowLogs\Pages\CreateChatbotFlowLog;
use App\Filament\Resources\ChatbotFlowLogs\Pages\EditChatbotFlowLog;
use App\Filament\Resources\ChatbotFlowLogs\Pages\ListChatbotFlowLogs;
use App\Filament\Resources\ChatbotFlowLogs\Schemas\ChatbotFlowLogForm;
use App\Filament\Resources\ChatbotFlowLogs\Tables\ChatbotFlowLogsTable;
use App\Models\ChatbotFlowLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ChatbotFlowLogResource extends Resource
{
    protected static ?string $model = ChatbotFlowLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Chatbot';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Flow logs';

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
        return ChatbotFlowLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChatbotFlowLogsTable::configure($table);
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
            'index' => ListChatbotFlowLogs::route('/'),
            'create' => CreateChatbotFlowLog::route('/create'),
            'edit' => EditChatbotFlowLog::route('/{record}/edit'),
        ];
    }
}
