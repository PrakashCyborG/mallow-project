<?php

namespace Tests\Feature;

use App\Jobs\SendOrderConfirmation;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_can_be_created_successfully(): void
    {
        Queue::fake();

        $product = Product::create([
            'name' => 'Laptop',
            'code' => 'LAP001',
            'unit_price' => 50000,
            'tax_percentage' => 18,
            'stock_on_hand' => 10,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Prakash',
            'customer_email' => 'prakash@gmail.com',
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('orders', [
            'customer_id' => 1,
            'grand_total' => 118000,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_on_hand' => 8,
        ]);

        Queue::assertPushed(SendOrderConfirmation::class);
    }

    public function test_order_can_be_created_with_multiple_products(): void
    {
        $firstProduct = Product::create([
            'name' => 'Bread',
            'code' => 'BRD001',
            'unit_price' => 50,
            'tax_percentage' => 5,
            'stock_on_hand' => 8,
        ]);
        $secondProduct = Product::create([
            'name' => 'Milk',
            'code' => 'MLK001',
            'unit_price' => 30,
            'tax_percentage' => 10,
            'stock_on_hand' => 6,
        ]);

        $this->postJson('/api/orders', [
            'customer_name' => 'Prakash',
            'customer_email' => 'prakash@example.com',
            'products' => [
                ['product_id' => $firstProduct->id, 'quantity' => 2],
                ['product_id' => $secondProduct->id, 'quantity' => 3],
            ],
        ])->assertCreated()
            ->assertJsonPath('order.subtotal', '190.00')
            ->assertJsonPath('order.tax_amount', '14.00')
            ->assertJsonPath('order.grand_total', '204.00')
            ->assertJsonCount(2, 'order.order_items');

        $this->assertDatabaseHas('products', [
            'id' => $firstProduct->id,
            'stock_on_hand' => 6,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $secondProduct->id,
            'stock_on_hand' => 3,
        ]);
    }

    public function test_billing_page_shows_customer_product_and_payment_controls(): void
    {
        Product::create([
            'name' => 'Bread',
            'code' => 'BRD002',
            'unit_price' => 50,
            'tax_percentage' => 5,
            'stock_on_hand' => 8,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('customer_email', false)
            ->assertSee('Add Product')
            ->assertSee('amount_given', false)
            ->assertSee('Low Stock Alert');
    }

    public function test_billing_page_only_lists_products_with_stock(): void
    {
        Product::create([
            'name' => 'Available Keyboard',
            'code' => 'KEY010',
            'unit_price' => 2499,
            'tax_percentage' => 18,
            'stock_on_hand' => 5,
        ]);
        Product::create([
            'name' => 'Sold Out Keyboard',
            'code' => 'KEY011',
            'unit_price' => 2499,
            'tax_percentage' => 18,
            'stock_on_hand' => 0,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Available Keyboard')
            ->assertDontSee('Sold Out Keyboard');
    }

    public function test_orders_can_be_listed_and_viewed(): void
    {
        $customer = Customer::create([
            'name' => 'Mira Shah',
            'email' => 'mira@example.com',
        ]);
        $product = Product::create([
            'name' => 'USB-C Hub',
            'code' => 'HUB010',
            'unit_price' => 1899,
            'tax_percentage' => 18,
            'stock_on_hand' => 10,
        ]);
        $order = $customer->orders()->create([
            'subtotal' => 1899,
            'tax_amount' => 341.82,
            'grand_total' => 2240.82,
        ]);
        $order->orderItems()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 1899,
            'tax_percentage' => 18,
            'subtotal' => 1899,
            'tax_amount' => 341.82,
            'total' => 2240.82,
        ]);

        $this->get(route('orders.index'))
            ->assertOk()
            ->assertSee($customer->name)
            ->assertSee($customer->email);

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Order Items')
            ->assertSee($product->name)
            ->assertSee('2,240.82');
    }

    public function test_web_order_submission_redirects_to_order_details(): void
    {
        Queue::fake();

        $product = Product::create([
            'name' => 'Desk Lamp',
            'code' => 'LMP010',
            'unit_price' => 100,
            'tax_percentage' => 10,
            'stock_on_hand' => 4,
        ]);

        $this->post(route('orders.store'), [
            'customer_name' => 'Mira Shah',
            'customer_email' => 'mira@example.com',
            'products' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ])->assertRedirect(route('orders.show', 1))
            ->assertSessionHas('success', 'Order created successfully');

        $this->assertDatabaseHas('orders', [
            'id' => 1,
            'subtotal' => 200,
            'tax_amount' => 20,
            'grand_total' => 220,
        ]);
        Queue::assertPushed(SendOrderConfirmation::class);
    }

    public function test_order_request_validates_customer_and_product_details(): void
    {
        $this->postJson('/api/orders', [
            'customer_name' => '',
            'customer_email' => 'not-an-email',
            'products' => [
                ['product_id' => 999, 'quantity' => 0],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'customer_name',
                'customer_email',
                'products.0.product_id',
                'products.0.quantity',
            ]);
    }

    public function test_order_failure_rolls_back_prior_stock_changes_and_customer_creation(): void
    {
        Queue::fake();

        $firstProduct = Product::create([
            'name' => 'USB-C Cable',
            'code' => 'CBL010',
            'unit_price' => 399,
            'tax_percentage' => 18,
            'stock_on_hand' => 5,
        ]);
        $secondProduct = Product::create([
            'name' => 'Power Bank',
            'code' => 'PWB010',
            'unit_price' => 2199,
            'tax_percentage' => 18,
            'stock_on_hand' => 1,
        ]);

        $this->postJson('/api/orders', [
            'customer_name' => 'Asha Patel',
            'customer_email' => 'asha@example.com',
            'products' => [
                ['product_id' => $firstProduct->id, 'quantity' => 2],
                ['product_id' => $secondProduct->id, 'quantity' => 2],
            ],
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Insufficient stock for Power Bank');

        $this->assertDatabaseMissing('customers', ['email' => 'asha@example.com']);
        $this->assertDatabaseMissing('orders', ['customer_id' => 1]);
        $this->assertDatabaseHas('products', [
            'id' => $firstProduct->id,
            'stock_on_hand' => 5,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $secondProduct->id,
            'stock_on_hand' => 1,
        ]);
        Queue::assertNothingPushed();
    }

    public function test_order_fails_when_stock_is_insufficient(): void
    {
        $product = Product::create([
            'name' => 'Laptop',
            'code' => 'LAP002',
            'unit_price' => 50000,
            'tax_percentage' => 18,
            'stock_on_hand' => 1,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Prakash',
            'customer_email' => 'prakash@gmail.com',
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertUnprocessable();

        $this->assertDatabaseMissing('orders', [
            'customer_id' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_on_hand' => 1,
        ]);
    }

    public function test_customer_can_view_order_history(): void
    {
        $product = Product::create([
            'name' => 'Laptop',
            'code' => 'LAP003',
            'unit_price' => 50000,
            'tax_percentage' => 18,
            'stock_on_hand' => 10,
        ]);

        $this->postJson('/api/orders', [
            'customer_name' => 'Prakash',
            'customer_email' => 'prakash@gmail.com',
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ])->assertCreated();

        $response = $this->getJson('/api/customers/prakash@gmail.com/orders');

        $response->assertOk()
            ->assertJsonFragment([
                'email' => 'prakash@gmail.com',
            ]);
    }

    public function test_customer_lookup_returns_name_for_existing_email(): void
    {
        Customer::create([
            'name' => 'Prakash Raj',
            'email' => 'prakash@example.com',
        ]);

        $this->getJson('/api/customers/lookup?email=prakash@example.com')
            ->assertOk()
            ->assertExactJson([
                'exists' => true,
                'name' => 'Prakash Raj',
            ]);
    }

    public function test_customer_lookup_identifies_new_email(): void
    {
        $this->getJson('/api/customers/lookup?email=new@example.com')
            ->assertOk()
            ->assertExactJson([
                'exists' => false,
                'name' => null,
            ]);
    }

    public function test_low_stock_products_can_be_retrieved(): void
    {
        Product::create([
            'name' => 'Low Stock Laptop',
            'code' => 'LAP004',
            'unit_price' => 50000,
            'tax_percentage' => 18,
            'stock_on_hand' => 3,
        ]);

        Product::create([
            'name' => 'Available Laptop',
            'code' => 'LAP005',
            'unit_price' => 50000,
            'tax_percentage' => 18,
            'stock_on_hand' => 15,
        ]);

        $response = $this->getJson('/api/products/low-stock?threshold=10');

        $response->assertOk()
            ->assertJsonFragment([
                'code' => 'LAP004',
            ])
            ->assertJsonMissing([
                'code' => 'LAP005',
            ]);
    }
}
