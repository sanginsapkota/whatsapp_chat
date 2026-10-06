<?php

namespace App\Filament\Rider\Pages;

use App\Enums\OrderStatus;
use App\Models\DeliveryTracking;
use App\Services\Orders\OrderStatusService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;
use InvalidArgumentException;

class MyDeliveries extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?string $title = 'My deliveries';

    protected string $view = 'filament.rider.pages.my-deliveries';

    public static function getNavigationLabel(): string
    {
        return 'My deliveries';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => DeliveryTracking::query()
                ->with(['order.customer', 'order.address'])
                ->where('rider_id', auth()->id())
                ->whereIn('status', ['assigned', 'preparing', 'out_for_delivery'])
                ->latest('assigned_at'))
            ->defaultSort('assigned_at', 'asc')
            ->columns([
                TextColumn::make('order.order_number')
                    ->label('Order')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('order.customer.name')
                    ->label('Customer')
                    ->description(fn (DeliveryTracking $r) => $r->order->customer?->phone_number),
                TextColumn::make('order.address')
                    ->label('Address')
                    ->formatStateUsing(fn ($state, DeliveryTracking $r) => $r->order->address?->singleLine() ?? '—')
                    ->wrap(),
                TextColumn::make('order.total_amount')
                    ->label('Collect (COD)')
                    ->money('NPR'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color()),
            ])
            ->recordActions([
                Action::make('startDelivery')
                    ->label('Start delivery')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('primary')
                    ->visible(fn (DeliveryTracking $r) => $r->order->status === OrderStatus::Preparing)
                    ->requiresConfirmation()
                    ->action(fn (DeliveryTracking $r) => $this->updateDeliveryStatus($r, OrderStatus::OutForDelivery)),

                Action::make('markDelivered')
                    ->label('Mark delivered')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (DeliveryTracking $r) => $r->order->status === OrderStatus::OutForDelivery)
                    ->schema([
                        TextInput::make('location')
                            ->label('Drop-off note (optional)')
                            ->placeholder('e.g. handed to customer at gate'),
                    ])
                    ->action(fn (array $data, DeliveryTracking $r) => $this->updateDeliveryStatus($r, OrderStatus::Delivered, $data['location'] ?? null)),
            ]);
    }

    public function updateDeliveryStatus(DeliveryTracking $tracking, OrderStatus $target, ?string $note = null): void
    {
        try {
            app(OrderStatusService::class)->transition($tracking->order, $target, $note);
        } catch (InvalidArgumentException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            return;
        }

        Notification::make()->success()->title('Delivery updated')->send();
    }
}
