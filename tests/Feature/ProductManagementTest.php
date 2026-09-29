<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_unused_product_can_be_deleted(): void
    {
        $product = Product::create([
            'name' => 'Wireless Mouse',
            'code' => 'MOU001',
            'unit_price' => 799,
            'tax_percentage' => 18,
            'stock_on_hand' => 20,
        ]);

        $this->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('success', 'Product deleted successfully.');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_products_can_be_listed(): void
    {
        $product = $this->createProduct();

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee($product->code);
    }

    public function test_product_can_be_created(): void
    {
        $this->post(route('products.store'), [
            'name' => 'USB-C Hub',
            'code' => 'HUB001',
            'unit_price' => 1899,
            'tax_percentage' => 18,
            'stock_on_hand' => 25,
        ])->assertRedirect(route('products.index'))
            ->assertSessionHas('success', 'Product created successfully.');

        $this->assertDatabaseHas('products', [
            'code' => 'HUB001',
            'unit_price' => 1899,
            'stock_on_hand' => 25,
        ]);
    }

    public function test_product_creation_validates_required_and_numeric_fields(): void
    {
        $this->post(route('products.store'), [
            'name' => '',
            'code' => '',
            'unit_price' => 0,
            'tax_percentage' => 101,
            'stock_on_hand' => -1,
        ])->assertSessionHasErrors([
            'name',
            'code',
            'unit_price',
            'tax_percentage',
            'stock_on_hand',
        ]);
    }

    public function test_product_can_be_updated_without_changing_its_code(): void
    {
        $product = $this->createProduct();

        $this->put(route('products.update', $product), [
            'name' => 'Updated Mouse',
            'code' => $product->code,
            'unit_price' => 899,
            'tax_percentage' => 12,
            'stock_on_hand' => 30,
        ])->assertRedirect(route('products.index'))
            ->assertSessionHas('success', 'Product updated successfully.');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Mouse',
            'unit_price' => 899,
            'tax_percentage' => 12,
            'stock_on_hand' => 30,
        ]);
    }

    public function test_product_code_must_remain_unique_when_updating(): void
    {
        $product = $this->createProduct();
        $otherProduct = Product::create([
            'name' => 'Keyboard',
            'code' => 'KEY001',
            'unit_price' => 2499,
            'tax_percentage' => 18,
            'stock_on_hand' => 10,
        ]);

        $this->put(route('products.update', $product), [
            'name' => $product->name,
            'code' => $otherProduct->code,
            'unit_price' => $product->unit_price,
            'tax_percentage' => $product->tax_percentage,
            'stock_on_hand' => $product->stock_on_hand,
        ])->assertSessionHasErrors('code');
    }

    public function test_product_used_in_an_order_cannot_be_deleted(): void
    {
        $product = $this->createProduct();
        $customer = Customer::create([
            'name' => 'Asha Patel',
            'email' => 'asha@example.com',
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'subtotal' => 799,
            'tax_amount' => 143.82,
            'grand_total' => 942.82,
        ]);
        $order->orderItems()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 799,
            'tax_percentage' => 18,
            'subtotal' => 799,
            'tax_amount' => 143.82,
            'total' => 942.82,
        ]);

        $this->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('error', 'This product is already used in an order and cannot be deleted.');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_product_seeder_can_run_repeatedly_without_duplicates(): void
    {
        $seeder = new ProductSeeder();

        $seeder->run();
        $seeder->run();

        $this->assertDatabaseCount('products', 10);
        $this->assertDatabaseHas('products', [
            'code' => 'PRD-001',
            'name' => 'Wireless Mouse',
        ]);
    }

    private function createProduct(): Product
    {
        return Product::create([
            'name' => 'Wireless Mouse',
            'code' => 'MOU001',
            'unit_price' => 799,
            'tax_percentage' => 18,
            'stock_on_hand' => 20,
        ]);
    }
}