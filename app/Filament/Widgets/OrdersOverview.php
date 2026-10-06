<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\SessionStatus;
use App\Models\Order;
use App\Models\WhatsappSession;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrdersOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $todayOrders = Order::whereDate('created_at', today());

        $openStatuses = [
            OrderStatus::Pending->value,
            OrderStatus::Confirmed->value,
            OrderStatus::Preparing->value,
            OrderStatus::OutForDelivery->value,
            OrderStatus::ReadyForPickup->value,
        ];

        return [
            Stat::make('Orders today', (string) $todayOrders->clone()->count())
                ->description('Placed since midnight')
                ->color('primary'),

            Stat::make('Revenue today', 'NPR '.number_format(
                (float) $todayOrders->clone()
                    ->whereNotIn('status', [OrderStatus::Cancelled->value])
                    ->sum('total_amount'),
                2,
            ))->color('success'),

            Stat::make('Open orders', (string) Order::whereIn('status', $openStatuses)->count())
                ->description('Awaiting fulfilment')
                ->color('warning'),

            Stat::make('Active chats', (string) WhatsappSession::where('session_status', SessionStatus::Active->value)->count())
                ->description('Live WhatsApp sessions')
                ->color('info'),
        ];
    }
}
