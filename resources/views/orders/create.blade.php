@extends('layouts.app')

@section('title', 'New Order')

@section('content')

<div class="billing-heading d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-uppercase small fw-semibold mb-1">Store Billing</p>
        <h1 class="h3 mb-0">New Order</h1>
    </div>
</div>

@if ($errors->any())
<div class="alert alert-danger">
    @foreach ($errors->all() as $error)
    <p class="mb-0">{{ $error }}</p>
    @endforeach
</div>
@endif

<form action="{{ route('orders.store') }}" method="POST" id="order-form">
    @csrf

    <section class="billing-section mb-4">
        <h2 class="h5 mb-3">Customer</h2>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="customer_email" class="form-label">Email</label>
                <input type="email" name="customer_email" id="customer_email" class="form-control" placeholder="e.g. thomas@example.com" value="{{ old('customer_email') }}" autocomplete="email" required>
                <div id="customer_status" class="form-text" role="status"></div>
            </div>

            <div class="col-md-6 mb-3">
                <label for="customer_name" class="form-label">Name</label>
                <input type="text" name="customer_name" id="customer_name" class="form-control" placeholder="Enter customer name" value="{{ old('customer_name') }}" autocomplete="name" required>
            </div>
        </div>
    </section>

    <section class="billing-section mb-4">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Products</h2>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle billing-table">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="quantity-column">Qty</th>
                                <th>Price</th>
                                <th>Line Total</th>
                                <th aria-label="Actions"></th>
                            </tr>
                        </thead>
                        <tbody id="product-rows"></tbody>
                    </table>
                </div>

                <div class="text-end mt-2">
                    <button type="button" class="btn btn-primary btn-sm" id="add-product" {{ $products->isEmpty() ? 'disabled' : '' }}>
                        + Add Product
                    </button>
                </div>
            </div>

            <aside class="col-lg-4">
                <div class="low-stock-panel h-100">
                    <h2 class="h5">Low Stock Alert</h2>
                    <div id="low_stock" role="status" aria-live="polite">
                        Loading low-stock products...
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <section class="billing-section payment-section mb-4">
        <div class="row g-4 align-items-end">
            <div class="col-lg-6">
                <h2 class="h5 mb-3">Payment</h2>
                <div class="payment-summary">
                    <div class="summary-line">
                        <span>Subtotal</span>
                        <strong id="subtotal">₹0.00</strong>
                    </div>
                    <div class="summary-line">
                        <span>Tax</span>
                        <strong id="tax_amount">₹0.00</strong>
                    </div>
                    <div class="summary-line summary-total">
                        <span>Grand Total</span>
                        <strong id="grand_total">₹0.00</strong>
                    </div>

                    <div class="amount-given mt-3">
                        <label for="amount_given" class="form-label fw-semibold">Amount Given by Customer</label>
                        <input type="number" name="amount_given" id="amount_given" class="form-control" min="0" step="0.01" value="{{ old('amount_given') }}" placeholder="Enter amount received">
                    </div>

                    <div class="summary-line change-line mt-3">
                        <span id="change_label">Balance to Return</span>
                        <strong id="change_amount">₹0.00</strong>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 text-lg-end">
                <button type="submit" class="btn btn-success btn-lg px-5">
                    Generate Bill
                </button>
            </div>
        </div>
    </section>
</form>

<template id="product-row-template">
    <tr class="product-row">
        <td>
            <select class="form-select product-select" required>
                <option value="">Select Product</option>
                @foreach ($products as $product)
                <option value="{{ $product->id }}" data-price="{{ $product->unit_price }}" data-tax="{{ $product->tax_percentage }}" data-stock="{{ $product->stock_on_hand }}">
                    {{ $product->name }} ({{ $product->code }})
                </option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" class="form-control quantity-input" min="1" value="1" required>
        </td>
        <td class="unit-price">₹0.00</td>
        <td class="line-total fw-semibold">₹0.00</td>
        <td class="text-end">
            <button type="button" class="btn btn-sm btn-outline-danger remove-product" aria-label="Remove product">Rmv</button>
        </td>
    </tr>
</template>

@endsection

@section('scripts')

<script>
    const productRows = document.getElementById('product-rows');
    const rowTemplate = document.getElementById('product-row-template');
    const oldProducts = @js(old('products', [['product_id' => '', 'quantity' => 1]]));
    const customerEmail = document.getElementById('customer_email');
    const customerName = document.getElementById('customer_name');
    const customerStatus = document.getElementById('customer_status');
    let lookupNumber = 0;

    function formatAmount(cents) {
        return '₹' + (cents / 100).toFixed(2);
    }

    function addProductRow(productId = '', quantity = 1) {
        const row = rowTemplate.content.firstElementChild.cloneNode(true);
        const rowNumber = productRows.children.length;
        const select = row.querySelector('.product-select');
        const quantityInput = row.querySelector('.quantity-input');

        select.name = `products[${rowNumber}][product_id]`;
        quantityInput.name = `products[${rowNumber}][quantity]`;
        select.value = productId;
        quantityInput.value = quantity;

        select.addEventListener('change', updateTotals);
        quantityInput.addEventListener('input', updateTotals);
        row.querySelector('.remove-product').addEventListener('click', function() {
            if (productRows.children.length === 1) {
                select.value = '';
                quantityInput.value = 1;
            } else {
                row.remove();
                updateRowNames();
            }

            updateTotals();
        });

        productRows.appendChild(row);
        updateRowNames();
        updateTotals();
    }

    function updateRowNames() {
        Array.from(productRows.children).forEach(function(row, index) {
            row.querySelector('.product-select').name = `products[${index}][product_id]`;
            row.querySelector('.quantity-input').name = `products[${index}][quantity]`;
        });
    }

    function updateTotals() {
        let subtotalCents = 0;
        let taxCents = 0;

        Array.from(productRows.children).forEach(function(row) {
            const select = row.querySelector('.product-select');
            const quantityInput = row.querySelector('.quantity-input');
            const option = select.options[select.selectedIndex];
            const quantity = Number(quantityInput.value) || 0;
            const priceCents = Math.round((Number(option.dataset.price) || 0) * 100);
            const taxPercentage = Number(option.dataset.tax) || 0;
            const lineSubtotal = priceCents * quantity;
            const lineTax = Math.round(lineSubtotal * taxPercentage / 100);

            subtotalCents += lineSubtotal;
            taxCents += lineTax;
            row.querySelector('.unit-price').textContent = formatAmount(priceCents);
            row.querySelector('.line-total').textContent = formatAmount(lineSubtotal + lineTax);
            quantityInput.max = option.dataset.stock || '';
        });

        const grandTotalCents = subtotalCents + taxCents;
        const amountGivenCents = Math.round((Number(document.getElementById('amount_given').value) || 0) * 100);
        const changeCents = Math.max(amountGivenCents - grandTotalCents, 0);
        const dueCents = Math.max(grandTotalCents - amountGivenCents, 0);

        document.getElementById('subtotal').textContent = formatAmount(subtotalCents);
        document.getElementById('tax_amount').textContent = formatAmount(taxCents);
        document.getElementById('grand_total').textContent = formatAmount(grandTotalCents);
        document.getElementById('change_label').textContent = amountGivenCents >= grandTotalCents ?
            'Balance to Return' :
            'Amount Due';
        document.getElementById('change_amount').textContent = formatAmount(
            amountGivenCents >= grandTotalCents ? changeCents : dueCents
        );
    }

    async function lookupCustomer() {
        const email = customerEmail.value.trim();
        const currentLookup = ++lookupNumber;

        customerName.value = '';
        customerName.readOnly = false;
        customerStatus.textContent = '';

        if (!email || !customerEmail.checkValidity()) {
            return;
        }

        customerStatus.textContent = 'Checking customer...';

        try {
            const response = await fetch(`/api/customers/lookup?email=${encodeURIComponent(email)}`);
            const data = await response.json();

            if (currentLookup !== lookupNumber) {
                return;
            }

            if (!response.ok) {
                customerStatus.textContent = 'Could not check this email. Enter the customer name.';
                return;
            }

            if (data.exists) {
                customerName.value = data.name;
                customerName.readOnly = true;
                customerStatus.textContent = 'Existing customer found.';
            } else {
                customerStatus.textContent = 'New customer. Enter their name.';
            }
        } catch (error) {
            if (currentLookup === lookupNumber) {
                customerStatus.textContent = 'Could not check this email. Enter the customer name.';
            }
        }
    }

    async function loadLowStock() {
        const container = document.getElementById('low_stock');

        try {
            const response = await fetch('/api/products/low-stock?threshold=10');
            const data = await response.json();

            if (!response.ok) {
                container.textContent = 'Low-stock products load aagala.';
                return;
            }

            if (!data.products || data.products.length === 0) {
                container.textContent = 'No low-stock products.';
                return;
            }

            container.replaceChildren();
            data.products.forEach(function(product) {
                const row = document.createElement('div');
                const name = document.createElement('span');
                const stock = document.createElement('strong');

                row.className = 'low-stock-item';
                name.textContent = product.name;
                stock.textContent = `${product.stock_on_hand} units left`;
                row.append(name, stock);
                container.appendChild(row);
            });
        } catch (error) {
            container.textContent = 'Could not load low-stock products.';
        }
    }

    document.getElementById('add-product').addEventListener('click', function() {
        addProductRow();
    });
    document.getElementById('amount_given').addEventListener('input', updateTotals);
    customerEmail.addEventListener('change', lookupCustomer);

    oldProducts.forEach(function(item) {
        addProductRow(item.product_id || '', item.quantity || 1);
    });
    updateTotals();
    loadLowStock();
</script>

@endsection