<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    /**
     * Catalog structure: category => [product => [variant => [unit, price, min, step]]]
     */
    private const CATALOG = [
        'Chicken' => [
            'sort' => 1,
            'products' => [
                'Broiler Chicken' => [
                    ['Whole (with skin)', 'kg', 320, 0.5, 0.5],
                    ['Curry Cut', 'kg', 340, 0.5, 0.5],
                    ['Boneless', 'kg', 520, 0.5, 0.25],
                    ['Mince (Keema)', 'kg', 480, 0.5, 0.25],
                ],
                'Local Chicken (Local Kukhura)' => [
                    ['Whole', 'kg', 620, 0.5, 0.5],
                    ['Curry Cut', 'kg', 650, 0.5, 0.5],
                ],
                'Chicken Wings' => [
                    ['Pack', 'kg', 360, 0.5, 0.5],
                ],
                'Chicken Sausage' => [
                    ['Pack of 6', 'piece', 260, 1, 1],
                ],
            ],
        ],
        'Mutton (Khasi)' => [
            'sort' => 2,
            'products' => [
                'Goat Mutton (Khasi)' => [
                    ['Curry Cut (with bone)', 'kg', 1350, 0.5, 0.25],
                    ['Boneless', 'kg', 1650, 0.5, 0.25],
                    ['Ribs', 'kg', 1250, 0.5, 0.25],
                    ['Keema (Mince)', 'kg', 1500, 0.5, 0.25],
                ],
            ],
        ],
        'Buff (Buffalo)' => [
            'sort' => 3,
            'products' => [
                'Buff Meat' => [
                    ['Curry Cut', 'kg', 420, 0.5, 0.5],
                    ['Boneless', 'kg', 480, 0.5, 0.25],
                    ['Keema (Mince)', 'kg', 460, 0.5, 0.25],
                ],
                'Buff Sukuti (Dried)' => [
                    ['Pack 250g', 'piece', 420, 1, 1],
                ],
            ],
        ],
        'Pork' => [
            'sort' => 4,
            'products' => [
                'Pork' => [
                    ['Curry Cut', 'kg', 550, 0.5, 0.5],
                    ['Belly', 'kg', 620, 0.5, 0.25],
                    ['Ribs', 'kg', 600, 0.5, 0.25],
                ],
            ],
        ],
        'Fish' => [
            'sort' => 5,
            'products' => [
                'Rahu Fish' => [
                    ['Whole (cleaned)', 'kg', 420, 0.5, 0.5],
                    ['Steak Cut', 'kg', 460, 0.5, 0.5],
                ],
                'Bachuwa Fish' => [
                    ['Whole (cleaned)', 'kg', 380, 0.5, 0.5],
                ],
            ],
        ],
        'Ready to Cook' => [
            'sort' => 6,
            'products' => [
                'Chicken MoMo Filling' => [
                    ['Pack 500g', 'piece', 320, 1, 1],
                ],
                'Marinated Chicken Sekuwa' => [
                    ['Pack 500g', 'piece', 380, 1, 1],
                ],
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::CATALOG as $categoryName => $categoryData) {
            $category = Category::updateOrCreate(
                ['slug' => Str::slug($categoryName)],
                [
                    'name' => $categoryName,
                    'is_active' => true,
                    'sort_order' => $categoryData['sort'],
                ],
            );

            $productSort = 0;

            foreach ($categoryData['products'] as $productName => $variants) {
                $product = Product::updateOrCreate(
                    ['slug' => Str::slug($productName)],
                    [
                        'name' => $productName,
                        'category_id' => $category->id,
                        'is_active' => true,
                        'sort_order' => $productSort++,
                    ],
                );

                foreach ($variants as [$variantName, $unit, $price, $min, $step]) {
                    ProductVariant::updateOrCreate(
                        ['product_id' => $product->id, 'variant_name' => $variantName],
                        [
                            'unit' => $unit,
                            'price_per_unit' => $price,
                            'min_quantity' => $min,
                            'step_quantity' => $step,
                            'is_active' => true,
                        ],
                    );
                }
            }
        }
    }
}
