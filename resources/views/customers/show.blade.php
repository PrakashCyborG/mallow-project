@extends('layouts.app')

@section('title', 'Customer Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3>Customer Details</h3>

    <div>
        <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-primary">Edit Customer</a>
        <a href="{{ route('customers.index') }}" class="btn btn-light">Back</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card border-0 shadow-sm p-4 mb-4">
    <h5 class="mb-3">Customer Information</h5>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="text-muted">Name</label>
            <p class="fw-semibold">{{ $customer->name }}</p>
        </div>

        <div class="col-md-6 mb-3">
            <label class="text-muted">Email</label>
            <p class="fw-semibold">{{ $customer->email }}</p>
        </div>

        <div class="col-md-6">
            <label class="text-muted">Joined Date</label>
            <p>{{ $customer->created_at->format('d M Y') }}</p>
        </div>

        <div class="col-md-6">
            <label class="text-muted">Total Orders</label>
            <p>{{ $customer->orders->count() }}</p>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0">Order History</h5>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Order ID</th>
                    <th>Items</th>
                    <th>Subtotal</th>
                    <th>Tax</th>
                    <th>Grand Total</th>
                    <th>Date</th>
                </tr>
            </thead>

            <tbody>
                @forelse($customer->orders as $order)
                    <tr>
                        <td>#{{ $order->id }}</td>
                        <td>{{ $order->orderItems->count() }}</td>
                        <td>₹{{ number_format($order->subtotal, 2) }}</td>
                        <td>₹{{ number_format($order->tax_amount, 2) }}</td>
                        <td class="fw-semibold">₹{{ number_format($order->grand_total, 2) }}</td>
                        <td>{{ $order->created_at->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No orders found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection