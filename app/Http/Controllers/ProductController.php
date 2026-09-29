<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::latest()->get();

        return view('products.index', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:products,code',
            'unit_price' => 'required|numeric|min:0.01',
            'tax_percentage' => 'required|numeric|min:0|max:100',
            'stock_on_hand' => 'required|integer|min:0',
        ]);

        Product::create($validated);

        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:products,code,' . $product->id,
            'unit_price' => 'required|numeric|min:0.01',
            'tax_percentage' => 'required|numeric|min:0|max:100',
            'stock_on_hand' => 'required|integer|min:0',
        ]);

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        if ($product->orderItems()->exists()) {
            return redirect()->route('products.index')->with('error', 'This product is already used in an order and cannot be deleted.');
        }
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }

    public function lowStock(Request $request): JsonResponse
    {
        $threshold = $request->query('threshold', 10);

        $products = Product::where('stock_on_hand', '<', $threshold)->get();

        return response()->json([
            'products' => $products,
        ]);
    }
}
