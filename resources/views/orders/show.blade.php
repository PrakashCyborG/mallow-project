@extends('layouts.app')

@section('title', 'Order Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3>Order #{{ $order->id }}</h3>
        <p class="text-muted mb-0">Order details and billing information</p>
    </div>

    <a href="{{ route('orders.index') }}" class="btn btn-light">Back to Orders</a>
</div>

<div class="card border-0 shadow-sm p-4 mb-4">
    <h5 class="mb-3">Customer Information</h5>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="text-muted">Customer Name</label>
            <p class="fw-semibold">{{ $order->customer->name }}</p>
        </div>

        <div class="col-md-6 mb-3">
            <label class="text-muted">Email Address</label>
            <p class="fw-semibold">{{ $order->customer->email }}</p>
        </div>

        <div class="col-md-6">
            <label class="text-muted">Order Date</label>
            <p>{{ $order->created_at->format('d M Y, h:i A') }}</p>
        </div>

        <div class="col-md-6">
            <label class="text-muted">Order ID</label>
            <p>#{{ $order->id }}</p>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0">Order Items</h5>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Code</th>
                    <th>Unit Price</th>
                    <th>Quantity</th>
                    <th>Tax (%)</th>
                    <th>Subtotal</th>
                    <th>Tax Amount</th>
                    <th>Total</th>
                </tr>
            </thead>

            <tbody>
                @foreach($order->orderItems as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->product->name }}</td>
                        <td>{{ $item->product->code }}</td>
                        <td>₹{{ number_format($item->unit_price, 2) }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->tax_percentage }}%</td>
                        <td>₹{{ number_format($item->subtotal, 2) }}</td>
                        <td>₹{{ number_format($item->tax_amount, 2) }}</td>
                        <td class="fw-semibold">₹{{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="row justify-content-end">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm p-4">
            <h5 class="mb-3">Order Summary</h5>

            <div class="d-flex justify-content-between mb-3">
                <span class="text-muted">Subtotal</span>
                <span>₹{{ number_format($order->subtotal, 2) }}</span>
            </div>

            <div class="d-flex justify-content-between mb-3">
                <span class="text-muted">Tax Amount</span>
                <span>₹{{ number_format($order->tax_amount, 2) }}</span>
            </div>

            <hr>

            <div class="d-flex justify-content-between">
                <h5>Grand Total</h5>
                <h5 class="text-primary">₹{{ number_format($order->grand_total, 2) }}</h5>
            </div>
        </div>
    </div>
</div>
@endsection