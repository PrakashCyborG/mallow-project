@extends('layouts.app')

@section('title', 'Customers')

@section('content')
<div class="mb-4">
    <h3>Customers</h3>
    <p class="text-muted">Manage your customers</p>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Customer Name</th>
                    <th>Email</th>
                    <th>Orders</th>
                    <th>Joined Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="fw-semibold">{{ $customer->name }}</td>
                        <td>{{ $customer->email }}</td>
                        <td>{{ $customer->orders_count }}</td>
                        <td>{{ $customer->created_at->format('d M Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-sm btn-outline-primary">View</a>

                            <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>

                            <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this customer?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">No customers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection