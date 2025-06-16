<?php

        namespace App\Http\Controllers\API;

        use App\Http\Controllers\Controller;
        use App\Models\Category;
        use App\Models\Product;
        use Illuminate\Http\Request;
        use Illuminate\Support\Facades\Storage;
        use Illuminate\Support\Facades\Validator;

        class ProductController extends Controller
        {
            /**
             * Display a listing of products.
             *
             * @return \Illuminate\Http\JsonResponse
             */
            public function index()
            {
                $products = Product::with('category')->get();

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

                   if ($file->getSize() > 20480 * 1024) {
                       $validator->errors()->add('ar_model', 'The ar model must not be greater than 20MB.');
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
                $product = Product::with('category')->find($id);

                if (!$product) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product not found'
                    ], 404);
                }

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
                    'ar_model' => 'nullable|file|mimes:glb,gltf,usdz|max:20480',
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
                    'ar_model' => 'required|file|mimes:glb,gltf,usdz|max:20480', // 20MB max
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

                // Delete associated AR model if exists
                if ($product->ar_model_url) {
                    $path = str_replace('/storage/', '/public/', $product->ar_model_url);
                    if (Storage::exists($path)) {
                        Storage::delete($path);
                    }
                }

                $product->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Product deleted successfully'
                ]);
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
        }
