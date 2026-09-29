<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::latest()->get();
        return view('customers.index', compact('customers'));
    }

    public function show(Customer $customer)
    {
        $customer->load(['orders.orderItems.product']);

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:customers,email,' . $customer->id,
        ]);

        $customer->update($validated);

        return redirect()->route('customers.show', $customer->id)
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted successfully.');
    }

    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $customer = Customer::where('email', $validated['email'])->first();

        return response()->json([
            'exists' => $customer !== null,
            'name' => $customer?->name,
        ]);
    }

    public function orderHistory(string $email): JsonResponse
    {
        $customer = Customer::where('email', $email)
            ->with('orders.orderItems.product')
            ->first();

        if (!$customer) {
            return response()->json([
                'message' => 'Customer not found',
            ], 404);
        }

        return response()->json([
            'customer' => $customer,
            'orders' => $customer->orders,
        ]);
    }
}
