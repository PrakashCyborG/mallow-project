@extends('layouts.app')

@section('title', 'Orders')

@section('content')
<div class="mb-4">
    <h3>Orders</h3>
    <p class="text-muted">View all customer orders</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Subtotal</th>
                    <th>Tax</th>
                    <th>Grand Total</th>
                    <th>Order Date</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td class="fw-semibold">#{{ $order->id }}</td>
                        <td>{{ $order->customer->name }}</td>
                        <td>{{ $order->customer->email }}</td>
                        <td>₹{{ number_format($order->subtotal, 2) }}</td>
                        <td>₹{{ number_format($order->tax_amount, 2) }}</td>
                        <td class="fw-semibold">₹{{ number_format($order->grand_total, 2) }}</td>
                        <td>{{ $order->created_at->format('d M Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-outline-primary">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            No orders found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $orders->links() }}
</div>
@endsection