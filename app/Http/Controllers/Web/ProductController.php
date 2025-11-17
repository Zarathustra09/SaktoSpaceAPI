<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
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
        $products = Product::with('category', 'images')->get();
        $categories = Category::all();
        return view('products.index', compact('products', 'categories'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): JsonResponse
    {
        $product->load('category', 'images');
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
        Log::info('Request data (excluding files):', $request->except(['image', 'ar_model', 'additional_images']));
        Log::info('Has image file: ' . ($request->hasFile('image') ? 'YES' : 'NO'));
        Log::info('Has AR model file: ' . ($request->hasFile('ar_model') ? 'YES' : 'NO'));
        Log::info('Has additional images: ' . ($request->hasFile('additional_images') ? 'YES' : 'NO'));

        if ($request->hasFile('additional_images')) {
            Log::info('Additional images count: ' . count($request->file('additional_images')));
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

            // Allow files up to 100MB
            if ($file->getSize() > 102400 * 1024) {
                $validator->errors()->add('ar_model', 'The ar model must not be greater than 100MB.');
            }
        }

        // Add validation for additional images
        if ($request->hasFile('additional_images')) {
            $additionalImages = $request->file('additional_images');

            if (count($additionalImages) > 5) {
                return response()->json([
                    'success' => false,
                    'errors' => ['additional_images' => ['You can only upload a maximum of 5 additional images.']]
                ], 422);
            }

            foreach ($additionalImages as $index => $image) {
                if (!$image->isValid()) {
                    $validator->errors()->add("additional_images.{$index}", 'Invalid image file.');
                    continue;
                }

                if ($image->getSize() > 2048 * 1024) {
                    $validator->errors()->add("additional_images.{$index}", 'Image size must not exceed 2MB.');
                }

                if (!in_array(strtolower($image->getClientOriginalExtension()), ['jpg', 'jpeg', 'png', 'gif'])) {
                    $validator->errors()->add("additional_images.{$index}", 'Image must be jpg, jpeg, png, or gif.');
                }
            }
        }

        if ($validator->fails()) {
            Log::error('Validation failed:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except(['image', 'ar_model', 'additional_images']);
        Log::info('Base product data:', $data);

        // Handle main image upload
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

            // Handle additional images
            if ($request->hasFile('additional_images')) {
                Log::info('Processing additional images...');

                $imageDir = public_path('storage/images');
                if (!file_exists($imageDir)) {
                    mkdir($imageDir, 0755, true);
                }

                foreach ($request->file('additional_images') as $index => $image) {
                    if ($image->isValid()) {
                        try {
                            $imageName = time() . '_' . uniqid() . '_additional_' . $index . '.' . $image->getClientOriginalExtension();
                            $moved = $image->move($imageDir, $imageName);

                            if ($moved) {
                                $imageUrl = '/storage/images/' . $imageName;

                                ProductImage::create([
                                    'product_id' => $product->id,
                                    'url' => $imageUrl,
                                    'alt_text' => $product->name . ' - Additional Image ' . ($index + 1)
                                ]);

                                Log::info("Additional image {$index} uploaded successfully: {$imageUrl}");
                            }
                        } catch (\Exception $e) {
                            Log::error("Error uploading additional image {$index}: " . $e->getMessage());
                        }
                    }
                }
            }

            $product->load('category', 'images');

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
        Log::info('=== PRODUCT UPDATE REQUEST START ===');
        Log::info('Product ID: ' . $product->id);
        Log::info('Request data (excluding files):', $request->except(['image', 'ar_model', 'additional_images']));
        Log::info('Has image file: ' . ($request->hasFile('image') ? 'YES' : 'NO'));
        Log::info('Has AR model file: ' . ($request->hasFile('ar_model') ? 'YES' : 'NO'));
        Log::info('Has additional images: ' . ($request->hasFile('additional_images') ? 'YES' : 'NO'));
        Log::info('Deleted image IDs:', $request->input('deleted_image_ids', []));

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

            // Allow files up to 100MB
            if ($file->getSize() > 102400 * 1024) {
                $validator->errors()->add('ar_model', 'The ar model must not be greater than 100MB.');
            }
        }

        // Add validation for additional images
        if ($request->hasFile('additional_images')) {
            $additionalImages = $request->file('additional_images');

            // Count current additional images that won't be deleted
            $deletedImageIds = $request->input('deleted_image_ids', []);
            $currentImagesCount = $product->images()->whereNotIn('id', $deletedImageIds)->count();

            if ($currentImagesCount + count($additionalImages) > 5) {
                return response()->json([
                    'success' => false,
                    'errors' => ['additional_images' => ['You can only have a maximum of 5 additional images total.']]
                ], 422);
            }

            foreach ($additionalImages as $index => $image) {
                if (!$image->isValid()) {
                    $validator->errors()->add("additional_images.{$index}", 'Invalid image file.');
                    continue;
                }

                if ($image->getSize() > 2048 * 1024) {
                    $validator->errors()->add("additional_images.{$index}", 'Image size must not exceed 2MB.');
                }

                if (!in_array(strtolower($image->getClientOriginalExtension()), ['jpg', 'jpeg', 'png', 'gif'])) {
                    $validator->errors()->add("additional_images.{$index}", 'Image must be jpg, jpeg, png, or gif.');
                }
            }
        }

        if ($validator->fails()) {
            Log::error('Validation failed:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except(['image', 'ar_model', 'additional_images', 'deleted_image_ids']);

        // Handle main image upload
        if ($request->hasFile('image')) {
            try {
                // Delete old image if exists
                if ($product->image && file_exists(public_path($product->image))) {
                    unlink(public_path($product->image));
                    Log::info('Deleted old main image: ' . $product->image);
                }

                $imageDir = public_path('storage/images');
                if (!file_exists($imageDir)) {
                    mkdir($imageDir, 0755, true);
                }

                $image = $request->file('image');
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $moved = $image->move($imageDir, $imageName);

                if ($moved) {
                    $data['image'] = '/storage/images/' . $imageName;
                    Log::info('Main image updated successfully: ' . $data['image']);
                }
            } catch (\Exception $e) {
                Log::error('Exception during main image update: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'errors' => ['image' => ['Error uploading image: ' . $e->getMessage()]]
                ], 422);
            }
        }

        // Handle AR model upload
        if ($request->hasFile('ar_model')) {
            try {
                // Delete old AR model if exists
                if ($product->ar_model_url && file_exists(public_path($product->ar_model_url))) {
                    unlink(public_path($product->ar_model_url));
                    Log::info('Deleted old AR model: ' . $product->ar_model_url);
                }

                $arDir = public_path('storage/ar-models');
                if (!file_exists($arDir)) {
                    mkdir($arDir, 0755, true);
                }

                $arModel = $request->file('ar_model');
                $arModelName = time() . '_' . uniqid() . '.' . $arModel->getClientOriginalExtension();
                $moved = $arModel->move($arDir, $arModelName);

                if ($moved) {
                    $data['ar_model_url'] = '/storage/ar-models/' . $arModelName;
                    Log::info('AR model updated successfully: ' . $data['ar_model_url']);
                }
            } catch (\Exception $e) {
                Log::error('Exception during AR model update: ' . $e->getMessage());
            }
        }

        try {
            // Update product data
            $product->update($data);

            // Handle deleted additional images
            $deletedImageIds = $request->input('deleted_image_ids', []);
            if (!empty($deletedImageIds)) {
                Log::info('Processing deleted additional images:', $deletedImageIds);

                $imagesToDelete = ProductImage::where('product_id', $product->id)
                    ->whereIn('id', $deletedImageIds)
                    ->get();

                foreach ($imagesToDelete as $imageRecord) {
                    // Delete file from filesystem
                    if (file_exists(public_path($imageRecord->url))) {
                        unlink(public_path($imageRecord->url));
                        Log::info('Deleted additional image file: ' . $imageRecord->url);
                    }

                    // Delete database record
                    $imageRecord->delete();
                    Log::info('Deleted additional image record: ' . $imageRecord->id);
                }
            }

            // Handle new additional images
            if ($request->hasFile('additional_images')) {
                Log::info('Processing new additional images...');

                $imageDir = public_path('storage/images');
                if (!file_exists($imageDir)) {
                    mkdir($imageDir, 0755, true);
                }

                foreach ($request->file('additional_images') as $index => $image) {
                    if ($image->isValid()) {
                        try {
                            $imageName = time() . '_' . uniqid() . '_additional_' . $index . '.' . $image->getClientOriginalExtension();
                            $moved = $image->move($imageDir, $imageName);

                            if ($moved) {
                                $imageUrl = '/storage/images/' . $imageName;

                                ProductImage::create([
                                    'product_id' => $product->id,
                                    'url' => $imageUrl,
                                    'alt_text' => $product->name . ' - Additional Image ' . ($index + 1)
                                ]);

                                Log::info("New additional image {$index} uploaded successfully: {$imageUrl}");
                            }
                        } catch (\Exception $e) {
                            Log::error("Error uploading new additional image {$index}: " . $e->getMessage());
                        }
                    }
                }
            }

            $product->load('category', 'images');

            Log::info('Product updated successfully');
            Log::info('Updated product data:', $product->toArray());

            return response()->json([
                'success' => 'Product updated successfully.',
                'data' => $product
            ]);
        } catch (\Exception $e) {
            Log::error('Exception during product update: ' . $e->getMessage());
            Log::error('Exception trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'errors' => ['general' => ['Error updating product: ' . $e->getMessage()]]
            ], 500);
        } finally {
            Log::info('=== PRODUCT UPDATE REQUEST END ===');
        }
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

        // Delete additional images
        foreach ($product->images as $image) {
            if (file_exists(public_path($image->url))) {
                unlink(public_path($image->url));
            }
        }

        $product->delete();

        return response()->json([
            'success' => 'Product deleted successfully.'
        ]);
    }
}
