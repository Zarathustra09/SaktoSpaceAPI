<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;

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
            $arModelName = time() . '_' . preg_replace('/\s+/', '_', $arModel->getClientOriginalName());
            $arModel->move(public_path('storage/ar-models'), $arModelName);
            $data['ar_model_url'] = '/storage/ar-models/' . $arModelName;
        }

        $product = Product::create($data);
        $product->load('category');

        return response()->json([
            'success' => 'Product created successfully.',
            'data' => $product
        ], 201);
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
