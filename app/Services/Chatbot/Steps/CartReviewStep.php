<?php

namespace App\Services\Chatbot\Steps;

use App\Services\Chatbot\CatalogPresenter;
use App\Services\Chatbot\FlowStep;
use App\Services\Chatbot\StepContext;
use App\Services\Chatbot\StepResult;
use App\Services\WhatsApp\OutboundMessage;

class CartReviewStep implements FlowStep
{
    public function __construct(private readonly CatalogPresenter $presenter) {}

    public static function key(): string
    {
        return 'cart_review';
    }

    public function handle(StepContext $context): StepResult
    {
        $cart = $context->cart();
        [$prefix, $action] = $context->reply();

        if (empty($cart)) {
            return StepResult::make(
                OutboundMessage::text('Your cart is empty. Let’s add something.'),
                $this->presenter->categoryList(),
            )->to('browse_categories');
        }

        $action = $prefix === 'cart' ? $action : $context->inputLower();

        return match ($action) {
            'add', 'add more' => StepResult::make($this->presenter->categoryList())->to('browse_categories'),

            'remove', 'remove last' => $this->removeLast($cart),

            'checkout', 'checkout order' => $this->checkout($context, $cart),

            default => StepResult::make(
                OutboundMessage::text('Choose an option below.'),
                $this->presenter->cartSummary($cart, (float) collect($cart)->sum('line_total')),
            ),
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $cart
     */
    private function removeLast(array $cart): StepResult
    {
        $removed = array_pop($cart);
        $subtotal = (float) collect($cart)->sum('line_total');

        if (empty($cart)) {
            return StepResult::make(
                OutboundMessage::text("Removed {$removed['product_name']}. Your cart is now empty."),
                $this->presenter->categoryList(),
            )->to('browse_categories')->with(['cart' => []]);
        }

        return StepResult::make(
            OutboundMessage::text("Removed {$removed['product_name']}."),
            $this->presenter->cartSummary($cart, $subtotal),
        )->with(['cart' => $cart]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $cart
     */
    private function checkout(StepContext $context, array $cart): StepResult
    {
        $subtotal = (float) collect($cart)->sum('line_total');
        $min = (float) config('ordering.min_order_amount');

        if ($min > 0 && $subtotal < $min) {
            return StepResult::make(
                OutboundMessage::text('Minimum order is '.$this->presenter->money($min).'. Please add a little more.'),
                $this->presenter->cartSummary($cart, $subtotal),
            );
        }

        return StepResult::make($this->presenter->fulfillmentButtons())->to('choose_fulfillment');
    }
}
