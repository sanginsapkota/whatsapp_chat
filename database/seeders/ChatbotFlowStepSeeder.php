<?php

namespace Database\Seeders;

use App\Models\ChatbotFlowStep;
use Illuminate\Database\Seeder;

class ChatbotFlowStepSeeder extends Seeder
{
    /**
     * step_key => [name, description, next_steps]
     */
    private const STEPS = [
        'welcome' => [
            'Welcome', 'Greets the customer and offers to start an order or view the menu.',
            ['browse_categories'],
        ],
        'browse_categories' => [
            'Browse categories', 'Shows the meat categories as an interactive list.',
            ['list_products'],
        ],
        'list_products' => [
            'List products', 'Shows products within the chosen category.',
            ['select_variant', 'browse_categories'],
        ],
        'select_variant' => [
            'Select cut / variant', 'Shows available cuts and prices for the chosen product.',
            ['enter_quantity', 'list_products'],
        ],
        'enter_quantity' => [
            'Enter quantity', 'Asks for the quantity in the variant unit (kg / piece).',
            ['select_preparation'],
        ],
        'select_preparation' => [
            'Preparation type', 'Asks how the meat should be prepared (normal cut, small pieces, mince, keep whole).',
            ['cart_review'],
        ],
        'cart_review' => [
            'Cart review', 'Shows the running cart and offers add more / checkout / remove.',
            ['browse_categories', 'choose_fulfillment', 'cart_review'],
        ],
        'choose_fulfillment' => [
            'Delivery or pickup', 'Asks whether the order is for delivery or store pickup.',
            ['choose_address', 'confirm_order'],
        ],
        'choose_address' => [
            'Delivery address', 'Lets the customer pick a saved address, share a location pin, or type a new address.',
            ['confirm_order'],
        ],
        'confirm_order' => [
            'Confirm order', 'Shows the full order summary with totals and asks for confirmation.',
            ['choose_payment', 'cart_review'],
        ],
        'choose_payment' => [
            'Payment method', 'Asks for the payment method (Cash on delivery/pickup for now).',
            ['order_placed'],
        ],
        'order_placed' => [
            'Order placed', 'Order has been created; sends the confirmation and order number.',
            ['post_order'],
        ],
        'post_order' => [
            'Post order / idle', 'Idle state after an order; any message restarts the flow at welcome.',
            ['welcome'],
        ],
        'handoff' => [
            'Human handoff', 'Customer asked for a human agent; bot is paused for this session.',
            ['welcome'],
        ],
    ];

    public function run(): void
    {
        foreach (self::STEPS as $key => [$name, $description, $nextSteps]) {
            ChatbotFlowStep::updateOrCreate(
                ['step_key' => $key],
                [
                    'step_name' => $name,
                    'description' => $description,
                    'next_steps' => $nextSteps,
                    'is_active' => true,
                ],
            );
        }
    }
}
