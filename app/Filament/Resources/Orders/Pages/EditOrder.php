<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\Orders\OrderStatusService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use InvalidArgumentException;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->changeStatusAction(),
            DeleteAction::make(),
        ];
    }

    private function changeStatusAction(): Action
    {
        return Action::make('changeStatus')
            ->label('Change status')
            ->icon('heroicon-o-arrow-path')
            ->visible(fn (Order $record) => filled($record->status->allowedNext()))
            ->schema([
                Select::make('status')
                    ->label('New status')
                    ->options(fn (Order $record) => collect($record->status->allowedNext())
                        ->mapWithKeys(fn (OrderStatus $s) => [$s->value => $s->label()])
                        ->all())
                    ->required(),
                Textarea::make('note')
                    ->label('Note (optional)')
                    ->rows(2),
            ])
            ->action(function (array $data, Order $record, OrderStatusService $service): void {
                try {
                    $service->transition($record, OrderStatus::from($data['status']), $data['note'] ?? null);
                } catch (InvalidArgumentException $e) {
                    Notification::make()->danger()->title($e->getMessage())->send();

                    return;
                }

                Notification::make()->success()->title('Order status updated')->send();
                $this->fillForm();
            });
    }
}
