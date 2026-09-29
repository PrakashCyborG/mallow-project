<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Exception;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('customer')
            ->latest()
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['customer', 'orderItems.product']);
        return view('orders.show', compact('order'));
    }

    public function create()
    {
        $products = Product::where('stock_on_hand', '>', 0)->get();

        return view('orders.create', compact('products'));
    }

    public function store(StoreOrderRequest $request, OrderService $orderService)
    {
        try {
            $order = $orderService->createOrder($request->validated());

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Order created successfully',
                    'order' => $order,
                ], 201);
            }

            return redirect()->route('orders.show', $order->id)
                ->with('success', 'Order created successfully');
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withInput()->withErrors([
                'order' => $e->getMessage(),
            ]);
        }
    }
}
