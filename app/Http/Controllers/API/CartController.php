<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    /**
     * Display the user's cart items
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $cart = Cart::where('user_id', Auth::id())->first();

        if (!$cart) {
            return response()->json([
                'success' => true,
                'data' => [
                    'items' => [],
                    'total' => 0
                ]
            ]);
        }

        $items = [];
        $total = 0;

        foreach ($cart->items as $item) {
            $product = Product::with('category')->find($item['product_id']);
            if ($product) {
                $itemData = [
                    'id' => $item['product_id'],
                    'name' => $product->name,
                    'price' => $product->price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $product->price * $item['quantity'],
                    'product' => $product
                ];
                $items[] = $itemData;
                $total += $itemData['subtotal'];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'total' => $total
            ]
        ]);
    }

    /**
     * Add a product to the cart
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::find($request->product_id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $requestedQuantity = $request->quantity;

        // Get or create user cart
        $cart = Cart::firstOrCreate(
            ['user_id' => Auth::id()],
            ['items' => []]
        );

        $items = $cart->items;
        $itemExists = false;

        // Check if product already exists in cart
        foreach ($items as &$item) {
            if ($item['product_id'] == $request->product_id) {
                $currentQuantity = $item['quantity'];
                $totalQuantity = $currentQuantity + $requestedQuantity;

                if ($totalQuantity > $product->stock) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The requested quantity exceeds available stock'
                    ], 400);
                }

                $item['quantity'] = $totalQuantity;
                $itemExists = true;
                break;
            }
        }

        // If product doesn't exist in cart, add it
        if (!$itemExists) {
            if ($requestedQuantity > $product->stock) {
                return response()->json([
                    'success' => false,
                    'message' => 'The requested quantity exceeds available stock'
                ], 400);
            }

            $items[] = [
                'product_id' => $request->product_id,
                'quantity' => $requestedQuantity
            ];
        }

        $cart->items = $items;
        $cart->save();

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart successfully'
        ]);
    }

    /**
     * Update cart item quantity
     *
     * @param Request $request
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateQuantity(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $cart = Cart::where('user_id', Auth::id())->first();

        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart not found'
            ], 404);
        }

        $items = $cart->items;
        $itemExists = false;

        foreach ($items as &$item) {
            if ($item['product_id'] == $productId) {
                $product = Product::find($productId);

                if (!$product) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product not found'
                    ], 404);
                }

                if ($request->quantity > $product->stock) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The requested quantity exceeds available stock'
                    ], 400);
                }

                $item['quantity'] = $request->quantity;
                $itemExists = true;
                break;
            }
        }

        if (!$itemExists) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found in cart'
            ], 404);
        }

        $cart->items = $items;
        $cart->save();

        return response()->json([
            'success' => true,
            'message' => 'Cart updated successfully'
        ]);
    }

    /**
     * Remove item from cart
     *
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeItem($productId)
    {
        $cart = Cart::where('user_id', Auth::id())->first();

        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart not found'
            ], 404);
        }

        $items = $cart->items;
        $found = false;

        foreach ($items as $key => $item) {
            if ($item['product_id'] == $productId) {
                unset($items[$key]);
                $found = true;
                break;
            }
        }

        if (!$found) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found in cart'
            ], 404);
        }

        // Reindex the array
        $cart->items = array_values($items);
        $cart->save();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart'
        ]);
    }

    /**
     * Clear the cart
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCart()
    {
        $cart = Cart::where('user_id', Auth::id())->first();

        if ($cart) {
            $cart->items = [];
            $cart->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully'
        ]);
    }

    /**
     * Get cart count
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCartCount()
    {
        $cart = Cart::where('user_id', Auth::id())->first();

        $count = 0;

        if ($cart) {
            $count = count($cart->items);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $count
            ]
        ]);
    }
}
