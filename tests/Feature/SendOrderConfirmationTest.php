<?php

namespace Tests\Feature;

use App\Jobs\SendOrderConfirmation;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SendOrderConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmation_job_logs_the_order_and_customer_email(): void
    {
        $customer = Customer::create([
            'name' => 'Mira Shah',
            'email' => 'mira@example.com',
        ]);
        $order = $customer->orders()->create([
            'subtotal' => 100,
            'tax_amount' => 18,
            'grand_total' => 118,
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Order confirmation email sent', [
                'order_id' => $order->id,
                'customer_email' => $customer->email,
            ]);

        (new SendOrderConfirmation($order))->handle();
    }
}