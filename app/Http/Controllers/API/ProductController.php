<?php

        namespace App\Http\Controllers\API;

        use App\Http\Controllers\Controller;
        use App\Models\Category;
        use App\Models\Product;
        use App\Models\Payment;
        use App\Models\Cart;
        use Illuminate\Http\Request;
        use Illuminate\Support\Facades\Storage;
        use Illuminate\Support\Facades\Validator;
        use Illuminate\Support\Facades\Log;
        use Illuminate\Support\Facades\DB;

        class ProductController extends Controller
        {
            /**
             * Display a listing of products.
             *
             * @return \Illuminate\Http\JsonResponse
             */
              public function index(Request $request)
              {
                  $categoryId = $request->query('category_id');

                  // Apply category filter only when category_id is provided and is a valid numeric value
                  $applyCategoryFilter = !is_null($categoryId) && $categoryId !== '' && is_numeric($categoryId);

                  $products = Product::with('category')
                      ->when($applyCategoryFilter, function ($query) use ($categoryId) {
                          $query->where('category_id', intval($categoryId));
                      })
                      ->get();

                  return response()->json([
                      'success' => true,
                      'data' => $products
                  ]);
              }

            /**
             * Store a newly created product.
             *
             * @param  \Illuminate\Http\Request  $request
             * @return \Illuminate\Http\JsonResponse
             */
           // Update ProductController.php
           public function store(Request $request)
           {
               // Modify validation to check extension instead of MIME type
               $validator = Validator::make($request->all(), [
                   'name' => 'required|string|max:255',
                   'description' => 'nullable|string',
                   'price' => 'required|numeric|min:0',
                   'stock' => 'required|integer|min:0',
                   'category_id' => 'required|exists:categories,id',
                   'image' => 'nullable|file|image|max:2048',
                   // Use custom validation for AR model
               ]);

               // Add custom validator for AR model file
               if ($request->hasFile('ar_model')) {
                   $file = $request->file('ar_model');
                   $extension = strtolower($file->getClientOriginalExtension());

                   if (!in_array($extension, ['glb', 'gltf', 'usdz'])) {
                       $validator->errors()->add('ar_model', 'The ar model must be a file of type: glb, gltf, usdz.');
                   }

                   // Allow files up to 100MB
                   if ($file->getSize() > 102400 * 1024) {
                       $validator->errors()->add('ar_model', 'The ar model must not be greater than 100MB.');
                   }
               }

               if ($validator->fails()) {
                   return response()->json([
                       'success' => false,
                       'errors' => $validator->errors()
                   ], 422);
               }

               $data = $request->except(['image', 'ar_model']);

               // Handle image upload
               if ($request->hasFile('image') && $request->file('image')->isValid()) {
                   // Create directory if it doesn't exist
                   if (!file_exists(public_path('storage/images'))) {
                       mkdir(public_path('storage/images'), 0755, true);
                   }

                   $image = $request->file('image');
                   $imageName = time() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());
                   $image->move(public_path('storage/images'), $imageName);
                   $data['image'] = '/storage/images/' . $imageName;
               }

               // Handle AR model upload
               if ($request->hasFile('ar_model') && $request->file('ar_model')->isValid()) {
                   // Create directory if it doesn't exist
                   if (!file_exists(public_path('storage/ar-models'))) {
                       mkdir(public_path('storage/ar-models'), 0755, true);
                   }

                   $arModel = $request->file('ar_model');
                   $extension = $arModel->getClientOriginalExtension();
                   $arModelName = time() . '_' . preg_replace('/\s+/', '_', $arModel->getClientOriginalName());

                   $arModel->move(public_path('storage/ar-models'), $arModelName);
                   $data['ar_model_url'] = '/storage/ar-models/' . $arModelName;
               }

               $product = Product::create($data);
               $product->load('category');

               return response()->json([
                   'success' => true,
                   'data' => $product
               ], 201);
           }

            /**
             * Display the specified product.
             *
             * @param  int  $id
             * @return \Illuminate\Http\JsonResponse
             */
            public function show($id)
            {
                $product = Product::with(['category', 'images'])->find($id);

                if (!$product) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product not found'
                    ], 404);
                }

                // Add rating information
                $product->average_rating = $product->averageRating();
                $product->total_ratings = $product->ratings()->count();
                $product->rating_breakdown = $product->getRatingBreakdown();

                return response()->json([
                    'success' => true,
                    'data' => $product
                ]);
            }

            /**
             * Update the specified product.
             *
             * @param  \Illuminate\Http\Request  $request
             * @param  int  $id
             * @return \Illuminate\Http\JsonResponse
             */
            public function update(Request $request, $id)
            {
                $product = Product::find($id);

                if (!$product) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product not found'
                    ], 404);
                }

                $validator = Validator::make($request->all(), [
                    'name' => 'sometimes|string|max:255',
                    'description' => 'nullable|string',
                    'price' => 'sometimes|numeric|min:0',
                    'stock' => 'sometimes|integer|min:0',
                    'category_id' => 'sometimes|exists:categories,id',
                    'image' => 'nullable|file|image|max:2048',
                    'ar_model' => 'nullable|file|mimes:glb,gltf,usdz|max:102400',
                    // Removed ar_scale and ar_placement_type from validation
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $validator->errors()
                    ], 422);
                }

                $data = $request->except(['image', 'ar_model']);

                // Handle image upload
                if ($request->hasFile('image')) {
                    // Delete old image if exists
                    if ($product->image && file_exists(public_path($product->image))) {
                        unlink(public_path($product->image));
                    }

                    $imageName = time() . '_' . $request->file('image')->getClientOriginalName();
                    $request->file('image')->move(public_path('storage/images'), $imageName);
                    $data['image'] = '/storage/images/' . $imageName;
                }

                // Handle AR model upload
                if ($request->hasFile('ar_model')) {
                    // Delete old AR model if exists
                    if ($product->ar_model_url && file_exists(public_path($product->ar_model_url))) {
                        unlink(public_path($product->ar_model_url));
                    }

                    $arModelName = time() . '_' . $request->file('ar_model')->getClientOriginalName();
                    $request->file('ar_model')->move(public_path('storage/ar-models'), $arModelName);
                    $data['ar_model_url'] = '/storage/ar-models/' . $arModelName;
                }

                $product->update($data);
                $product->load('category');

                return response()->json([
                    'success' => true,
                    'data' => $product
                ]);
            }

            /**
             * Upload a 3D model file for AR.
             *
             * @param  \Illuminate\Http\Request  $request
             * @param  int  $id
             * @return \Illuminate\Http\JsonResponse
             */
            public function uploadARModel(Request $request, $id)
            {
                $product = Product::find($id);

                if (!$product) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product not found'
                    ], 404);
                }

                $validator = Validator::make($request->all(), [
                    'ar_model' => 'required|file|mimes:glb,gltf,usdz|max:102400', // 100MB max
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $validator->errors()
                    ], 422);
                }

                if ($request->hasFile('ar_model')) {
                    // Delete old file if exists
                    if ($product->ar_model_url && file_exists(public_path($product->ar_model_url))) {
                        unlink(public_path($product->ar_model_url));
                    }

                    $arModelName = time() . '_' . $request->file('ar_model')->getClientOriginalName();
                    $request->file('ar_model')->move(public_path('storage/ar-models'), $arModelName);
                    $url = '/storage/ar-models/' . $arModelName;

                    $product->ar_model_url = $url;
                    $product->save();

                    return response()->json([
                        'success' => true,
                        'data' => [
                            'ar_model_url' => $url
                        ]
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'No file uploaded'
                ], 400);
            }

            /**
             * Get AR-ready products.
             *
             * @return \Illuminate\Http\JsonResponse
             */
            public function getARProducts()
            {
                $products = Product::whereNotNull('ar_model_url')
                    ->with('category')
                    ->get();

                return response()->json([
                    'success' => true,
                    'data' => $products
                ]);
            }

            /**
             * Remove the specified product.
             *
             * @param  int  $id
             * @return \Illuminate\Http\JsonResponse
             */
            public function destroy($id)
            {
                $product = Product::find($id);

                if (!$product) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product not found'
                    ], 404);
                }

                DB::beginTransaction();

                try {
                    Log::info('ProductController: Starting product deletion process', [
                        'product_id' => $id,
                        'product_name' => $product->name
                    ]);

                    // 1. Clean up payments that contain this product in purchased_items
                    $paymentsWithProduct = Payment::whereJsonContains('purchased_items', [['product_id' => $id]])->get();

                    Log::info('ProductController: Found payments containing this product', [
                        'product_id' => $id,
                        'payments_count' => $paymentsWithProduct->count(),
                        'payment_ids' => $paymentsWithProduct->pluck('id')->toArray()
                    ]);

                    foreach ($paymentsWithProduct as $payment) {
                        $purchasedItems = $payment->purchased_items ?? [];

                        // Filter out items with this product_id
                        $filteredItems = array_filter($purchasedItems, function($item) use ($id) {
                            return ($item['product_id'] ?? null) != $id;
                        });

                        if (empty($filteredItems)) {
                            // If no items left, delete the entire payment
                            Log::info('ProductController: Deleting payment with no remaining items', [
                                'payment_id' => $payment->id,
                                'product_id' => $id
                            ]);
                            $payment->delete();
                        } else {
                            // Update payment with remaining items and recalculate amount
                            $newAmount = array_sum(array_map(function($item) {
                                return ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
                            }, $filteredItems));

                            Log::info('ProductController: Updating payment with remaining items', [
                                'payment_id' => $payment->id,
                                'product_id' => $id,
                                'original_amount' => $payment->amount,
                                'new_amount' => $newAmount,
                                'remaining_items' => count($filteredItems)
                            ]);

                            $payment->update([
                                'purchased_items' => array_values($filteredItems),
                                'amount' => $newAmount
                            ]);
                        }
                    }

                    // 2. Clean up cart items that contain this product
                    $cartsWithProduct = Cart::whereNotNull('items')->get();

                    foreach ($cartsWithProduct as $cart) {
                        $cartItems = $cart->items ?? [];

                        // Filter out items with this product_id
                        $filteredCartItems = array_filter($cartItems, function($item) use ($id) {
                            return ($item['product_id'] ?? null) != $id;
                        });

                        if (count($filteredCartItems) != count($cartItems)) {
                            Log::info('ProductController: Updating cart items', [
                                'cart_id' => $cart->id,
                                'user_id' => $cart->user_id,
                                'product_id' => $id,
                                'original_items_count' => count($cartItems),
                                'remaining_items_count' => count($filteredCartItems)
                            ]);

                            $cart->update([
                                'items' => array_values($filteredCartItems)
                            ]);
                        }
                    }

                    // 3. Delete associated AR model file if exists
                    if ($product->ar_model_url) {
                        $arModelPath = public_path($product->ar_model_url);
                        if (file_exists($arModelPath)) {
                            unlink($arModelPath);
                            Log::info('ProductController: Deleted AR model file', [
                                'product_id' => $id,
                                'ar_model_path' => $arModelPath
                            ]);
                        }
                    }

                    // 4. Delete associated image file if exists
                    if ($product->image) {
                        $imagePath = public_path($product->image);
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                            Log::info('ProductController: Deleted product image', [
                                'product_id' => $id,
                                'image_path' => $imagePath
                            ]);
                        }
                    }

                    // 5. Finally delete the product
                    $product->delete();

                    Log::info('ProductController: Successfully deleted product and cleaned up references', [
                        'product_id' => $id,
                        'payments_processed' => $paymentsWithProduct->count(),
                        'carts_processed' => $cartsWithProduct->count()
                    ]);

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Product deleted successfully along with related cart items and payment references',
                        'cleanup_info' => [
                            'payments_affected' => $paymentsWithProduct->count(),
                            'carts_checked' => $cartsWithProduct->count()
                        ]
                    ]);

                } catch (\Exception $e) {
                    DB::rollback();

                    Log::error('ProductController: Error during product deletion', [
                        'product_id' => $id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Error deleting product: ' . $e->getMessage()
                    ], 500);
                }
            }

            /**
             * Get products by category.
             *
             * @param  int  $categoryId
             * @return \Illuminate\Http\JsonResponse
             */
            public function getByCategory($categoryId)
            {
                if (!Category::find($categoryId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Category not found'
                    ], 404);
                }

                $products = Product::where('category_id', $categoryId)
                    ->with('category')
                    ->get();

                return response()->json([
                    'success' => true,
                    'data' => $products
                ]);
            }


            /**
             * Search products by query string with optional category filter.
             *
             * @param  \Illuminate\Http\Request  $request
             * @return \Illuminate\Http\JsonResponse
             */
            public function search(Request $request)
            {
                $validator = Validator::make($request->all(), [
                    'query' => 'required|string|min:1',
                    'category_id' => 'nullable|exists:categories,id',
                    'limit' => 'nullable|integer|min:1|max:100'
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $validator->errors()
                    ], 422);
                }

                $query = $request->input('query');
                $categoryId = $request->input('category_id');
                $limit = $request->input('limit', 20);

                $products = Product::with('category')
                    ->when($categoryId, function ($q) use ($categoryId) {
                        return $q->where('category_id', $categoryId);
                    })
                    ->where(function ($q) use ($query) {
                        $q->where('name', 'LIKE', "%{$query}%")
                            ->orWhere('description', 'LIKE', "%{$query}%");
                    })
                    ->orderByRaw("
            CASE
                WHEN name LIKE ? THEN 1
                WHEN name LIKE ? THEN 2
                WHEN description LIKE ? THEN 3
                ELSE 4
            END
        ", ["{$query}%", "%{$query}%", "%{$query}%"])
                    ->limit($limit)
                    ->get();

                return response()->json([
                    'success' => true,
                    'data' => $products,
                    'query' => $query,
                    'category_id' => $categoryId,
                    'total_results' => $products->count()
                ]);
            }
        }
