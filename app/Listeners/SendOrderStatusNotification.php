<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Events\OrderStatusChanged;
use App\Jobs\SendWhatsAppMessage;
use App\Services\WhatsApp\OutboundMessage;

class SendOrderStatusNotification
{
    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order->loadMissing('customer');
        $customer = $order->customer;

        if ($customer === null) {
            return;
        }

        $body = $this->messageFor($order->status, $order->order_number, $order->order_type);

        if ($body === null) {
            return;
        }

        SendWhatsAppMessage::dispatch(
            $customer->phone_number,
            OutboundMessage::text($body)->toArray(),
            $customer->id,
        );
    }

    private function messageFor(OrderStatus $status, string $orderNumber, OrderType $type): ?string
    {
        $isPickup = $type === OrderType::Pickup;

        return match ($status) {
            OrderStatus::Confirmed => "✅ Order *{$orderNumber}* is confirmed. We’ll start preparing it shortly.",
            OrderStatus::Preparing => "👨‍🍳 Order *{$orderNumber}* is being prepared fresh.",
            OrderStatus::OutForDelivery => "🛵 Order *{$orderNumber}* is on the way. Please keep your phone handy.",
            OrderStatus::ReadyForPickup => "📦 Order *{$orderNumber}* is ready for pickup at ".config('ordering.pickup_address').'.',
            OrderStatus::Delivered => "🎉 Order *{$orderNumber}* has been delivered. Thank you for choosing Shuvakamana Butcher!",
            OrderStatus::Completed => $isPickup
                ? "🎉 Order *{$orderNumber}* is complete. Thank you — see you next time!"
                : null,
            OrderStatus::Cancelled => "❌ Order *{$orderNumber}* has been cancelled. Contact the shop if this is unexpected.",
            default => null,
        };
    }
}
