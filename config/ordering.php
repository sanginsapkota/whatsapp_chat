<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ordering rules for the WhatsApp chatbot
    |--------------------------------------------------------------------------
    */

    // Flat delivery fee (NPR) applied to delivery orders.
    'delivery_fee' => (float) env('ORDERING_DELIVERY_FEE', 100),

    // Waive the delivery fee when the item subtotal is at or above this amount.
    // Set to null to always charge the fee.
    'free_delivery_over' => env('ORDERING_FREE_DELIVERY_OVER') !== null
        ? (float) env('ORDERING_FREE_DELIVERY_OVER')
        : null,

    // Minimum item subtotal (NPR) required before checkout is allowed.
    'min_order_amount' => (float) env('ORDERING_MIN_ORDER_AMOUNT', 0),

    // Shown to the customer for pickup orders.
    'pickup_address' => env('ORDERING_PICKUP_ADDRESS', 'New Shuvakamana Butcher, Main Road'),

    // Preparation options offered after quantity selection.
    'preparation_options' => [
        'normal' => 'Normal cut',
        'small' => 'Small pieces',
        'mince' => 'Mince / keema',
        'whole' => 'Keep whole',
        'clean_only' => 'Just clean',
    ],
];
