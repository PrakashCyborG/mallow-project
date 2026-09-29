<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Jobs\SendOrderConfirmation;

class OrderService
{
    /**
     * Create a new class instance.
     */
    public function createOrder(array $data)
    {
        return DB::transaction(function () use ($data) {

            $customer = Customer::firstOrCreate(
                ['email' => $data['customer_email']],
                ['name' => $data['customer_name']]
            );

            $subtotal = 0;
            $taxAmount = 0;
            $orderItems = [];

            foreach ($data['products'] as $item) {

                $product = Product::where('id', $item['product_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($product->stock_on_hand < $item['quantity']) {
                    throw new Exception(
                        "Insufficient stock for {$product->name}"
                    );
                }

                $quantity = $item['quantity'];
                $itemSubtotal = $product->unit_price * $quantity;
                $itemTax = $itemSubtotal * $product->tax_percentage / 100;
                $itemTotal = $itemSubtotal + $itemTax;

                $subtotal += $itemSubtotal;
                $taxAmount += $itemTax;

                $orderItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $itemSubtotal,
                    'tax_amount' => $itemTax,
                    'total' => $itemTotal,
                ];

                $product->decrement('stock_on_hand', $quantity);
            }

            $order = Order::create([
                'customer_id' => $customer->id,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $subtotal + $taxAmount,
            ]);

            foreach ($orderItems as $item) {
                $order->orderItems()->create([
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['product']->unit_price,
                    'tax_percentage' => $item['product']->tax_percentage,
                    'subtotal' => $item['subtotal'],
                    'tax_amount' => $item['tax_amount'],
                    'total' => $item['total'],
                ]);
            }

            SendOrderConfirmation::dispatch($order)->afterCommit();
            
            return $order->load('customer', 'orderItems.product');
        });
    }
}
