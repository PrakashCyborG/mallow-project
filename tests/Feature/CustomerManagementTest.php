<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_list_displays_customers(): void
    {
        $customer = $this->createCustomer();

        $this->get(route('customers.index'))
            ->assertOk()
            ->assertSee($customer->name)
            ->assertSee($customer->email);
    }

    public function test_customer_details_and_edit_pages_are_available(): void
    {
        $customer = $this->createCustomer();

        $this->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Customer Details')
            ->assertSee($customer->email);

        $this->get(route('customers.edit', $customer))
            ->assertOk()
            ->assertSee('Edit Customer')
            ->assertSee($customer->name);
    }

    public function test_customer_can_be_updated(): void
    {
        $customer = $this->createCustomer();

        $this->put(route('customers.update', $customer), [
            'name' => 'Asha Patel',
            'email' => $customer->email,
        ])->assertRedirect(route('customers.show', $customer))
            ->assertSessionHas('success', 'Customer updated successfully.');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Asha Patel',
        ]);
    }

    public function test_customer_update_rejects_an_email_used_by_another_customer(): void
    {
        $customer = $this->createCustomer();
        $otherCustomer = Customer::create([
            'name' => 'Ravi Kumar',
            'email' => 'ravi@example.com',
        ]);

        $this->put(route('customers.update', $customer), [
            'name' => $customer->name,
            'email' => $otherCustomer->email,
        ])->assertSessionHasErrors('email');
    }

    public function test_customer_can_be_soft_deleted(): void
    {
        $customer = $this->createCustomer();

        $this->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'));

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_customer_lookup_rejects_an_invalid_email(): void
    {
        $this->getJson('/api/customers/lookup?email=not-an-email')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_order_history_returns_not_found_for_unknown_customer(): void
    {
        $this->getJson('/api/customers/unknown@example.com/orders')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Customer not found']);
    }

    private function createCustomer(): Customer
    {
        return Customer::create([
            'name' => 'Mira Shah',
            'email' => 'mira@example.com',
        ]);
    }
}