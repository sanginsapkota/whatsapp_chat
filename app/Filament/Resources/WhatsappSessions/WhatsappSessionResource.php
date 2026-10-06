<?php

namespace App\Filament\Resources\WhatsappSessions;

use App\Filament\Resources\WhatsappSessions\Pages\CreateWhatsappSession;
use App\Filament\Resources\WhatsappSessions\Pages\EditWhatsappSession;
use App\Filament\Resources\WhatsappSessions\Pages\ListWhatsappSessions;
use App\Filament\Resources\WhatsappSessions\Schemas\WhatsappSessionForm;
use App\Filament\Resources\WhatsappSessions\Tables\WhatsappSessionsTable;
use App\Models\WhatsappSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WhatsappSessionResource extends Resource
{
    protected static ?string $model = WhatsappSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Chatbot';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Sessions';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return WhatsappSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WhatsappSessionsTable::configure($table);
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
            'index' => ListWhatsappSessions::route('/'),
            'create' => CreateWhatsappSession::route('/create'),
            'edit' => EditWhatsappSession::route('/{record}/edit'),
        ];
    }
}
