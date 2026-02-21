<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * Display a listing of active/inactive products.
     * Public access.
     *
     * GET /api/products
     */
    public function index(): AnonymousResourceCollection
    {
        return ProductResource::collection(Product::all());
    }

    /**
     * Display a single product.
     * Public access.
     *
     * GET /api/products/{product}
     */
    public function show(Product $product): ProductResource
    {
        return new ProductResource($product);
    }

    /**
     * Store a newly created product.
     * Admin only.
     *
     * POST /api/products
     */
    public function store(Request $request): ProductResource
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'numeric', 'min:0'],
            'status'      => ['required', 'in:active,inactive'],
        ]);

        $product = Product::create($validated);

        return new ProductResource($product);
    }

    /**
     * Update an existing product.
     * Admin only.
     *
     * PUT/PATCH /api/products/{product}
     */
    public function update(Request $request, Product $product): ProductResource
    {
        $validated = $request->validate([
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price'       => ['sometimes', 'required', 'numeric', 'min:0'],
            'status'      => ['sometimes', 'required', 'in:active,inactive'],
        ]);

        $product->update($validated);

        return new ProductResource($product);
    }

    /**
     * Delete a product.
     * Admin only.
     *
     * DELETE /api/products/{product}
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'data' => [
                'message' => 'Product deleted successfully.',
            ],
        ]);
    }
}
