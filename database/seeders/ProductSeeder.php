<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed the products table.
     */
    public function run(): void
    {
        $products = [
            ['name' => 'Wireless Mouse', 'code' => 'PRD-001', 'unit_price' => 799.00, 'tax_percentage' => 18.00, 'stock_on_hand' => 50],
            ['name' => 'Mechanical Keyboard', 'code' => 'PRD-002', 'unit_price' => 2499.00, 'tax_percentage' => 18.00, 'stock_on_hand' => 30],
            ['name' => 'USB-C Hub', 'code' => 'PRD-003', 'unit_price' => 1899.00, 'tax_percentage' => 18.00, 'stock_on_hand' => 25],
            ['name' => 'Laptop Stand', 'code' => 'PRD-004', 'unit_price' => 1299.00, 'tax_percentage' => 18.00, 'stock_on_hand' => 20],
            ['name' => 'USB-C Cable', 'code' => 'PRD-005', 'unit_price' => 399.00, 'tax_percentage' => 18.00, 'stock_on_hand' => 100],
            ['name' => 'Wireless Headphones', 'code' => 'PRD-006', 'unit_price' => 3499.00, 'tax_percentage' => 18.00, 'stock_on_hand' => 15],
            ['name' => 'Webcam', 'code' => 'PRD-007', 'unit_price' => 2799.00, 'tax_percentage' => 18.00, 'stock_on_hand' => 18],
            ['name' => 'Portable SSD 1TB', 'code' => 'PRD-008', 'unit_price' => 6999.00, 'tax_percentage' => 18.00, 'stock_on_hand' => 12],
            ['name' => 'Desk Lamp', 'code' => 'PRD-009', 'unit_price' => 999.00, 'tax_percentage' => 18.00, 'stock_on_hand' => 22],
            ['name' => 'Power Bank 20000mAh', 'code' => 'PRD-010', 'unit_price' => 2199.00, 'tax_percentage' => 18.00, 'stock_on_hand' => 16],
        ];

        Product::upsert($products, ['code'], ['name', 'unit_price', 'tax_percentage', 'stock_on_hand']);
    }
}