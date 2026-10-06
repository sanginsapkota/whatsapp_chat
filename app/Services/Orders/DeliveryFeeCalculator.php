<?php

namespace App\Services\Orders;

class DeliveryFeeCalculator
{
    public function for(float $subtotal, string $fulfilment): float
    {
        if ($fulfilment !== 'delivery') {
            return 0.0;
        }

        $fee = (float) config('ordering.delivery_fee');
        $freeOver = config('ordering.free_delivery_over');

        if ($freeOver !== null && $subtotal >= (float) $freeOver) {
            return 0.0;
        }

        return $fee;
    }
}
