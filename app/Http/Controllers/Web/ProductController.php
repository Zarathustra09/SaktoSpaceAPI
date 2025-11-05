<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $products = Product::with('category')->get();
        $categories = Category::all();
        return view('products.index', compact('products', 'categories'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): JsonResponse
    {
        $product->load('category');
        return response()->json($product);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        Log::info('=== PRODUCT STORE REQUEST START ===');
        Log::info('Request method: ' . $request->method());
        Log::info('Request URL: ' . $request->fullUrl());
        Log::info('Request data (excluding files):', $request->except(['image', 'ar_model']));
        Log::info('Has image file: ' . ($request->hasFile('image') ? 'YES' : 'NO'));
        Log::info('Has AR model file: ' . ($request->hasFile('ar_model') ? 'YES' : 'NO'));

        if ($request->hasFile('image')) {
            $imageFile = $request->file('image');
            Log::info('Image file details:', [
                'original_name' => $imageFile->getClientOriginalName(),
                'mime_type' => $imageFile->getMimeType(),
                'size' => $imageFile->getSize(),
                'is_valid' => $imageFile->isValid(),
                'error' => $imageFile->getError()
            ]);
        }

        if ($request->hasFile('ar_model')) {
            $arFile = $request->file('ar_model');
            Log::info('AR model file details:', [
                'original_name' => $arFile->getClientOriginalName(),
                'mime_type' => $arFile->getMimeType(),
                'size' => $arFile->getSize(),
                'is_valid' => $arFile->isValid(),
                'error' => $arFile->getError()
            ]);
        }

        // Use same validation as API with custom AR model validation
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|file|image|max:2048',
        ]);

        // Add custom validator for AR model file
        if ($request->hasFile('ar_model')) {
            $file = $request->file('ar_model');
            $extension = strtolower($file->getClientOriginalExtension());

            Log::info('AR model file extension: ' . $extension);

            if (!in_array($extension, ['glb', 'gltf', 'usdz'])) {
                $validator->errors()->add('ar_model', 'The ar model must be a file of type: glb, gltf, usdz.');
            }

            if ($file->getSize() > 20480 * 1024) {
                $validator->errors()->add('ar_model', 'The ar model must not be greater than 20MB.');
            }
        }

        if ($validator->fails()) {
            Log::error('Validation failed:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except(['image', 'ar_model']);
        Log::info('Base product data:', $data);

        // Handle image upload
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            try {
                Log::info('Processing image upload...');

                // Ensure storage directories exist
                $imageDir = public_path('storage/images');
                if (!file_exists($imageDir)) {
                    mkdir($imageDir, 0755, true);
                    Log::info('Created images directory: ' . $imageDir);
                }

                $image = $request->file('image');
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

                Log::info('Generated image name: ' . $imageName);
                Log::info('Target path: ' . $imageDir . '/' . $imageName);

                // Move the file
                $moved = $image->move($imageDir, $imageName);

                if ($moved) {
                    $data['image'] = '/storage/images/' . $imageName;
                    Log::info('Image uploaded successfully: ' . $data['image']);

                    // Verify file exists
                    if (file_exists($imageDir . '/' . $imageName)) {
                        Log::info('Image file verified to exist on filesystem');
                        Log::info('File size on disk: ' . filesize($imageDir . '/' . $imageName) . ' bytes');
                    } else {
                        Log::error('Image file not found after upload!');
                    }
                } else {
                    Log::error('Failed to move uploaded image file');
                    return response()->json([
                        'success' => false,
                        'errors' => ['image' => ['Failed to upload image file']]
                    ], 422);
                }
            } catch (\Exception $e) {
                Log::error('Exception during image upload: ' . $e->getMessage());
                Log::error('Exception trace: ' . $e->getTraceAsString());
                return response()->json([
                    'success' => false,
                    'errors' => ['image' => ['Error uploading image: ' . $e->getMessage()]]
                ], 422);
            }
        } else {
            Log::info('No valid image file to process');
        }

        // Handle AR model upload
        if ($request->hasFile('ar_model') && $request->file('ar_model')->isValid()) {
            try {
                Log::info('Processing AR model upload...');

                // Ensure storage directories exist
                $arDir = public_path('storage/ar-models');
                if (!file_exists($arDir)) {
                    mkdir($arDir, 0755, true);
                    Log::info('Created ar-models directory: ' . $arDir);
                }

                $arModel = $request->file('ar_model');
                $arModelName = time() . '_' . uniqid() . '.' . $arModel->getClientOriginalExtension();

                Log::info('Generated AR model name: ' . $arModelName);

                $moved = $arModel->move($arDir, $arModelName);

                if ($moved) {
                    $data['ar_model_url'] = '/storage/ar-models/' . $arModelName;
                    Log::info('AR model uploaded successfully: ' . $data['ar_model_url']);
                } else {
                    Log::error('Failed to move uploaded AR model file');
                }
            } catch (\Exception $e) {
                Log::error('Exception during AR model upload: ' . $e->getMessage());
                Log::error('Exception trace: ' . $e->getTraceAsString());
            }
        } else {
            Log::info('No valid AR model file to process');
        }

        Log::info('Final product data before creation:', $data);

        try {
            $product = Product::create($data);
            $product->load('category');

            Log::info('Product created successfully with ID: ' . $product->id);
            Log::info('Created product data:', $product->toArray());

            return response()->json([
                'success' => 'Product created successfully.',
                'data' => $product
            ], 201);
        } catch (\Exception $e) {
            Log::error('Exception during product creation: ' . $e->getMessage());
            Log::error('Exception trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'errors' => ['general' => ['Error creating product: ' . $e->getMessage()]]
            ], 500);
        } finally {
            Log::info('=== PRODUCT STORE REQUEST END ===');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'category_id' => 'sometimes|exists:categories,id',
            'image' => 'nullable|file|image|max:2048',
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
            'success' => 'Product updated successfully.',
            'data' => $product
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): JsonResponse
    {
        // Delete associated files
        if ($product->image && file_exists(public_path($product->image))) {
            unlink(public_path($product->image));
        }

        if ($product->ar_model_url && file_exists(public_path($product->ar_model_url))) {
            unlink(public_path($product->ar_model_url));
        }

        $product->delete();

        return response()->json([
            'success' => 'Product deleted successfully.'
        ]);
    }
}
