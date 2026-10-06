<?php

namespace App\Services\Orders;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderStatusService
{
    /**
     * Move an order to a new status, guarding illegal transitions and keeping
     * the related payment / delivery-tracking rows in sync.
     */
    public function transition(Order $order, OrderStatus $target, ?string $note = null): Order
    {
        $current = $order->status;

        if ($current === $target) {
            return $order;
        }

        if (! $current->canTransitionTo($target)) {
            throw new InvalidArgumentException(
                "Cannot move order {$order->order_number} from {$current->value} to {$target->value}.",
            );
        }

        return DB::transaction(function () use ($order, $current, $target, $note) {
            $order->status = $target;

            if ($target === OrderStatus::Confirmed && $order->ordered_at === null) {
                $order->ordered_at = now();
            }

            $order->save();

            $this->syncDeliveryTracking($order, $target, $note);
            $this->syncPayment($order, $target);

            event(new OrderStatusChanged($order->fresh(), $current, $target));

            return $order;
        });
    }

    private function syncDeliveryTracking(Order $order, OrderStatus $target, ?string $note): void
    {
        if (! $order->isDelivery()) {
            return;
        }

        $tracking = $order->deliveryTracking()->firstOrCreate([], [
            'status' => DeliveryStatus::Preparing,
        ]);

        $map = [
            OrderStatus::Preparing->value => DeliveryStatus::Preparing,
            OrderStatus::OutForDelivery->value => DeliveryStatus::OutForDelivery,
            OrderStatus::Delivered->value => DeliveryStatus::Delivered,
        ];

        if (! isset($map[$target->value])) {
            return;
        }

        // Don't clobber a rider assignment when the order merely moves to "preparing".
        if ($target === OrderStatus::Preparing && $tracking->rider_id !== null) {
            $tracking->status = DeliveryStatus::Assigned;
        } else {
            $tracking->status = $map[$target->value];
        }

        if ($note !== null) {
            $tracking->note = $note;
        }

        if ($target === OrderStatus::Delivered) {
            $tracking->delivered_at = now();
        }

        $tracking->save();
    }

    private function syncPayment(Order $order, OrderStatus $target): void
    {
        if (! in_array($target, [OrderStatus::Delivered, OrderStatus::Completed], true)) {
            return;
        }

        $payment = $order->payment;

        if ($payment && $payment->payment_status->value === 'pending') {
            $payment->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            $order->update(['payment_status' => 'paid']);
        }
    }
}
