<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use Illuminate\Http\Request;
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

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $ratings = $query->paginate(15);

        return response()->json($ratings);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'product_id' => 'required|exists:products,id',
            'comment' => 'nullable|string|max:1000',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        // Check if user already rated this product
        $existingRating = Rating::where('user_id', $request->user_id)
                               ->where('product_id', $request->product_id)
                               ->first();

        if ($existingRating) {
            return response()->json([
                'message' => 'You have already rated this product'
            ], 422);
        }

        $rating = Rating::create($request->all());
        $rating->load(['user', 'product']);

        return response()->json($rating, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Rating $rating)
    {
        $rating->load(['user', 'product']);
        return response()->json($rating);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Rating $rating)
    {
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
        $rating->delete();
        return response()->json(['message' => 'Rating deleted successfully']);
    }
}

