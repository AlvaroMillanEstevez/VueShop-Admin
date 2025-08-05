<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Retrieve a list of products.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $isAdmin = Auth::user()->role === 'admin';

        $query = Product::with('user');

        // Filter by user if not admin
        if (!$isAdmin) {
            $query->where('user_id', $userId);
        }

        // Additional filters
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->has('category')) {
            $query->where('category', $request->get('category'));
        }

        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        $products = $query->orderBy('created_at', 'desc')
                         ->paginate(15);

        // Transform data to include seller info if admin
        $products->getCollection()->transform(function ($product) use ($isAdmin) {
            $data = [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => (float)$product->price,
                'stock' => $product->stock,
                'sku' => $product->sku,
                'category' => $product->category,
                'image_url' => $product->image_url,
                'active' => $product->active,
                'created_at' => $product->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $product->updated_at->format('Y-m-d H:i:s'),
            ];

            // Include seller info if admin
            if ($isAdmin) {
                $data['seller'] = [
                    'id' => $product->user->id ?? null,
                    'name' => $product->user->name ?? 'Unassigned',
                    'email' => $product->user->email ?? '',
                ];
            }

            return $data;
        });

        return response()->json($products);
    }

    /**
     * Create a new product.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'sku' => 'required|string|max:100|unique:products,sku',
            'category' => 'required|string|max:100',
            'image_url' => 'nullable|url',
            'active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::create(array_merge(
            $request->all(),
            ['user_id' => Auth::id()]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product->load('user')
        ], 201);
    }

    /**
     * Retrieve a specific product.
     */
    public function show(Product $product)
    {
        $userId = Auth::id();
        $isAdmin = Auth::user()->role === 'admin';

        // Check if user can view this product
        if (!$isAdmin && $product->user_id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $product->load('user')
        ]);
    }

    /**
     * Update a specific product.
     */
    public function update(Request $request, Product $product)
    {
        $userId = Auth::id();
        $isAdmin = Auth::user()->role === 'admin';

        // Check if user can update this product
        if (!$isAdmin && $product->user_id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'sku' => 'sometimes|string|max:100|unique:products,sku,' . $product->id,
            'category' => 'sometimes|string|max:100',
            'image_url' => 'nullable|url',
            'active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $product->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product->load('user')
        ]);
    }

    /**
     * Delete a specific product.
     */
    public function destroy(Product $product)
    {
        $userId = Auth::id();
        $isAdmin = Auth::user()->role === 'admin';

        // Check if user can delete this product
        if (!$isAdmin && $product->user_id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Check if the product has associated orders
        if ($product->orderItems()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete product with associated orders'
            ], 422);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }
}
