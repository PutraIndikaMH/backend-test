<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /**
     * List all orders.
     * Admin only.
     *
     * GET /api/orders
     */
    public function index(): AnonymousResourceCollection
    {
        $orders = Order::with(['orderItems.product'])->latest()->get();

        return OrderResource::collection($orders);
    }

    /**
     * Display a single order.
     * Admin only.
     *
     * GET /api/orders/{order}
     */
    public function show(Order $order): OrderResource
    {
        $order->load('orderItems.product');

        return new OrderResource($order);
    }

    /**
     * Place a new order.
     * Public access.
     *
     * POST /api/orders
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name'          => ['required', 'string', 'max:255'],
            'customer_email'         => ['required', 'email', 'max:255'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'integer', 'exists:products,id'],
            'items.*.qty'            => ['required', 'integer', 'min:1'],
        ]);

        $order = DB::transaction(function () use ($validated) {
            $totalPrice = 0;
            $itemsToInsert = [];

            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);

                if ($product->status !== 'active') {
                    throw ValidationException::withMessages([
                        'items' => ["Product \"{$product->name}\" is not available for purchase."],
                    ]);
                }

                $price    = (float) $product->price;
                $qty      = (int) $item['qty'];
                $subtotal = $price * $qty;

                $totalPrice += $subtotal;

                $itemsToInsert[] = [
                    'product_id' => $product->id,
                    'qty'        => $qty,
                    'price'      => $price,      // snapshot of price at time of order
                    'subtotal'   => $subtotal,
                ];
            }

            $order = Order::create([
                'customer_name'  => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'status'         => 'pending',
                'total_price'    => $totalPrice,
            ]);

            $order->orderItems()->createMany($itemsToInsert);

            return $order->load('orderItems.product');
        });

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }
}
