<?php

namespace App\Services\Chatbot;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Services\WhatsApp\OutboundMessage;
use Illuminate\Support\Str;

/**
 * Builds the interactive WhatsApp messages the ordering flow sends: catalog
 * lists, preparation buttons, cart and order summaries.
 */
class CatalogPresenter
{
    public function money(float|string $amount): string
    {
        return 'NPR '.number_format((float) $amount, 2);
    }

    public function qty(float|string $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }

    public function categoryList(): OutboundMessage
    {
        $rows = Category::query()->active()->ordered()->limit(10)->get()
            ->map(fn (Category $c) => [
                'id' => "cat:{$c->id}",
                'title' => Str::limit($c->name, 24),
            ])->all();

        return OutboundMessage::list(
            'What would you like today? Pick a category to see the items.',
            'View categories',
            [['title' => 'Categories', 'rows' => $rows]],
            header: 'Shuvakamana Butcher',
        );
    }

    public function productList(Category $category): OutboundMessage
    {
        $rows = $category->products()->active()->ordered()->limit(10)->get()
            ->map(fn (Product $p) => [
                'id' => "prod:{$p->id}",
                'title' => Str::limit($p->name, 24),
            ])->all();

        return OutboundMessage::list(
            "{$category->name} — choose an item:",
            'View items',
            [['title' => $category->name, 'rows' => $rows]],
        );
    }

    public function variantList(Product $product): OutboundMessage
    {
        $rows = $product->variants()->active()->get()
            ->map(fn ($v) => [
                'id' => "var:{$v->id}",
                'title' => Str::limit($v->variant_name, 24),
                'description' => $this->money($v->price_per_unit).' / '.$v->unit,
            ])->all();

        return OutboundMessage::list(
            "{$product->name} — choose a cut:",
            'View cuts',
            [['title' => 'Available cuts', 'rows' => $rows]],
        );
    }

    public function preparationButtons(): OutboundMessage
    {
        $options = config('ordering.preparation_options');
        $rows = collect($options)
            ->map(fn ($label, $code) => ['id' => "prep:{$code}", 'title' => $label])
            ->values()
            ->all();

        return OutboundMessage::list(
            'How should we prepare it?',
            'Choose',
            [['title' => 'Preparation', 'rows' => $rows]],
        );
    }

    public function fulfillmentButtons(): OutboundMessage
    {
        return OutboundMessage::buttons('How would you like to get your order?', [
            ['id' => 'fulfil:delivery', 'title' => 'Delivery'],
            ['id' => 'fulfil:pickup', 'title' => 'Pickup'],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $cart
     */
    public function cartSummary(array $cart, float $subtotal): OutboundMessage
    {
        $lines = collect($cart)->map(fn ($item, $i) => sprintf(
            "%d. %s (%s)\n   %s %s × %s = %s",
            $i + 1,
            $item['product_name'],
            $item['variant_name'],
            $this->qty($item['quantity']),
            $item['unit'],
            $this->money($item['unit_price']),
            $this->money($item['line_total']),
        ))->implode("\n");

        $body = "🧺 Your cart\n\n{$lines}\n\nSubtotal: {$this->money($subtotal)}";

        return OutboundMessage::buttons($body, [
            ['id' => 'cart:add', 'title' => 'Add more'],
            ['id' => 'cart:checkout', 'title' => 'Checkout'],
            ['id' => 'cart:remove', 'title' => 'Remove last'],
        ]);
    }

    public function addressChoices(Customer $customer): OutboundMessage
    {
        $rows = $customer->addresses()->latest('is_default')->latest()->limit(8)->get()
            ->map(fn ($a) => [
                'id' => "addr:{$a->id}",
                'title' => Str::limit($a->label ?: $a->address_line1, 24),
                'description' => Str::limit($a->singleLine(), 68),
            ])->all();

        $rows[] = ['id' => 'addr:new', 'title' => 'Type a new address'];
        $rows[] = ['id' => 'addr:location', 'title' => 'Share location pin'];

        return OutboundMessage::list(
            'Where should we deliver?',
            'Choose address',
            [['title' => 'Delivery address', 'rows' => $rows]],
            footer: 'You can also send a location pin any time.',
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $cart
     */
    public function orderSummary(array $cart, float $subtotal, float $deliveryFee, string $fulfilment, string $where): OutboundMessage
    {
        $lines = collect($cart)->map(fn ($item) => sprintf(
            '• %s (%s) — %s %s × %s = %s',
            $item['product_name'],
            $item['variant_name'],
            $this->qty($item['quantity']),
            $item['unit'],
            $this->money($item['unit_price']),
            $this->money($item['line_total']),
        ))->implode("\n");

        $total = $subtotal + $deliveryFee;

        $body = "📋 Please confirm your order\n\n{$lines}\n\n"
            .'Subtotal: '.$this->money($subtotal)."\n"
            .($fulfilment === 'delivery' ? 'Delivery fee: '.$this->money($deliveryFee)."\n" : '')
            .'Total: '.$this->money($total)."\n\n"
            .ucfirst($fulfilment).': '.$where;

        return OutboundMessage::buttons($body, [
            ['id' => 'confirm:yes', 'title' => 'Confirm order'],
            ['id' => 'confirm:no', 'title' => 'Back to cart'],
        ]);
    }
}
