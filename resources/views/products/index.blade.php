@extends('layouts.app')

@section('title', 'Products')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1">Products</h3>
        <p class="text-muted mb-0">Manage your products and inventory</p>
    </div>

    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProductModal">
        + Create Product
    </button>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th>Code</th>
                    <th>Unit Price</th>
                    <th>Tax (%)</th>
                    <th>Stock</th>
                    <th>Created At</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="fw-semibold">{{ $product->name }}</td>
                        <td>{{ $product->code }}</td>
                        <td>₹{{ number_format($product->unit_price, 2) }}</td>
                        <td>{{ $product->tax_percentage }}%</td>
                        <td>
                            @if($product->stock_on_hand <= 10)
                                <span class="badge bg-danger">{{ $product->stock_on_hand }}</span>
                            @else
                                <span class="badge bg-success">{{ $product->stock_on_hand }}</span>
                            @endif
                        </td>
                        <td>{{ $product->created_at->format('d M Y') }}</td>

                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProductModal{{ $product->id }}">
                                Edit
                            </button>

                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteProductModal{{ $product->id }}">
                                Delete
                            </button>
                        </td>
                    </tr>

                    <div class="modal fade" id="editProductModal{{ $product->id }}" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form action="{{ route('products.update', $product->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')

                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Product</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Product Name</label>
                                            <input type="text" name="name" class="form-control" value="{{ $product->name }}" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Product Code</label>
                                            <input type="text" name="code" class="form-control" value="{{ $product->code }}" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Unit Price (₹)</label>
                                            <input type="number" name="unit_price" class="form-control" value="{{ $product->unit_price }}" min="0.01" step="0.01" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Tax Percentage (%)</label>
                                            <input type="number" name="tax_percentage" class="form-control" value="{{ $product->tax_percentage }}" min="0" max="100" step="0.01" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Stock on Hand</label>
                                            <input type="number" name="stock_on_hand" class="form-control" value="{{ $product->stock_on_hand }}" min="0" step="1" required>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Update Product</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="deleteProductModal{{ $product->id }}" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered modal-sm">
                            <div class="modal-content">
                                <form action="{{ route('products.destroy', $product->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')

                                    <div class="modal-header">
                                        <h5 class="modal-title">Delete Product</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body">
                                        Are you sure you want to delete <strong>{{ $product->name }}</strong>?
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-danger">Delete</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            No products found. Create your first product.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="createProductModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('products.store') }}" method="POST">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Create Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter product name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Product Code</label>
                        <input type="text" name="code" class="form-control" placeholder="e.g. LAP001" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Unit Price (₹)</label>
                        <input type="number" name="unit_price" class="form-control" placeholder="Enter unit price" min="0.01" step="0.01" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tax Percentage (%)</label>
                        <input type="number" name="tax_percentage" class="form-control" placeholder="e.g. 18" min="0" max="100" step="0.01" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Stock on Hand</label>
                        <input type="number" name="stock_on_hand" class="form-control" placeholder="Enter available stock" min="0" step="1" value="0" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection