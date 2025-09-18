<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Cart;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = auth()->user();

        // Get statistics
        $totalProducts = Product::count();
        $totalCategories = Category::count();

        // Get user's cart
        $userCart = $user->cart;
        $cartItemsCount = $userCart ? count($userCart->items) : 0;

        // Get user's payments
        $userPayments = Payment::where('user_id', $user->id)->sum('amount');
        $totalPayments = $userPayments ?: 0;

        // Get recent payments (last 5)
        $recentPayments = Payment::where('user_id', $user->id)
            ->orderBy('payment_date', 'desc')
            ->take(5)
            ->get();

        // Get categories with product counts
        $categoriesWithCounts = Category::withCount('products')->get();

        return view('home', compact(
            'totalProducts',
            'totalCategories',
            'cartItemsCount',
            'totalPayments',
            'recentPayments',
            'categoriesWithCounts',
            'userCart'
        ));
    }
}
