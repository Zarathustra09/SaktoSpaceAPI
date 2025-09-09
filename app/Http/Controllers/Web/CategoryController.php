<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $categories = Category::withCount('products')->get();
        return view('categories.index', compact('categories'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category): JsonResponse
    {
        return response()->json($category);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
            'type' => 'required|string|in:' . implode(',', Category::getTypes()),
        ]);

        $category = Category::create($request->all());

        return response()->json([
            'success' => 'Category created successfully.',
            'data' => $category
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
            'type' => 'required|string|in:' . implode(',', Category::getTypes()),
        ]);

        $category->update($request->all());

        return response()->json([
            'success' => 'Category updated successfully.',
            'data' => $category
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category): JsonResponse
    {
        // Check if category has products before deletion
        $hasProducts = $category->products()->exists();

        if ($hasProducts) {
            return response()->json([
                'error' => 'Cannot delete category with associated products.'
            ], 409);
        }

        $category->delete();

        return response()->json([
            'success' => 'Category deleted successfully.'
        ]);
    }
}
