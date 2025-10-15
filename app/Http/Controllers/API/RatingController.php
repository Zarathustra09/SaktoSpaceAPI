<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RatingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Rating::with(['user', 'product']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Remove user_id filter since we'll use auth user
        $ratings = $query->paginate(15);

        return response()->json($ratings);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'comment' => 'nullable|string|max:1000',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $userId = Auth::id();

        // Check if user already rated this product
        $existingRating = Rating::where('user_id', $userId)
                               ->where('product_id', $request->product_id)
                               ->first();

        if ($existingRating) {
            return response()->json([
                'message' => 'You have already rated this product'
            ], 422);
        }

        $rating = Rating::create([
            'user_id' => $userId,
            'product_id' => $request->product_id,
            'comment' => $request->comment,
            'rating' => $request->rating,
        ]);

        $rating->load(['user', 'product']);

        return response()->json($rating, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($productId)
    {
        $product = Product::with(['ratings.user', 'category'])->findOrFail($productId);

        // Add average rating to the response
        $product->average_rating = $product->averageRating();
        $product->total_ratings = $product->ratings()->count();

        return response()->json($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Rating $rating)
    {
        // Ensure user can only update their own rating
        if ($rating->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'You can only update your own ratings'
            ], 403);
        }

        $request->validate([
            'comment' => 'nullable|string|max:1000',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $rating->update($request->only(['comment', 'rating']));
        $rating->load(['user', 'product']);

        return response()->json($rating);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Rating $rating)
    {
        // Ensure user can only delete their own rating
        if ($rating->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'You can only delete your own ratings'
            ], 403);
        }

        $rating->delete();
        return response()->json(['message' => 'Rating deleted successfully']);
    }
}
