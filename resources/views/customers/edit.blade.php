@extends('layouts.app')

@section('title', 'Edit Customer')

@section('content')
<div class="mb-4">
    <h3>Edit Customer</h3>
    <p class="text-muted">Update customer information</p>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card border-0 shadow-sm p-4">
    <form action="{{ route('customers.update', $customer->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Customer Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $customer->name) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $customer->email) }}" required>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Update Customer</button>
            <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-light">Cancel</a>
        </div>
    </form>
</div>
@endsection