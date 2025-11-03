<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Cart;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        // Get basic statistics
        $totalProducts = Product::count();
        $totalCategories = Category::count();

        // Get user's cart
        $userCart = $user->cart;
        $cartItemsCount = $userCart ? count($userCart->items) : 0;

        // ADMIN-WIDE: fetch all completed payments (normalize status) and materialize
        $completedPayments = Payment::whereRaw('LOWER(TRIM(status)) = ?', ['completed'])->get();

        // Log fetched payments overview
        Log::info('Admin Dashboard: Fetched completed payments', [
            'count' => $completedPayments->count(),
            'payments' => $completedPayments->map(function ($p) {
                return [
                    'id' => $p->id,
                    'amount' => (float) $p->amount,
                    'status' => $p->status,
                    'payment_date' => optional($p->payment_date)->toDateTimeString(),
                    'purchased_items_type' => gettype($p->purchased_items),
                ];
            })->toArray(),
        ]);

        // Aggregates from materialized collection
        $totalOrders = $completedPayments->count();
        $totalPayments = $completedPayments->sum(fn ($p) => (float) $p->amount) ?: 0.0;
        $averageOrderValue = $totalOrders > 0 ? $totalPayments / $totalOrders : 0.0;

        // Count total purchased items and LOG items before aggregation
        $totalPurchasedItems = 0;
        foreach ($completedPayments as $payment) {
            $items = is_array($payment->purchased_items)
                ? $payment->purchased_items
                : (is_string($payment->purchased_items) ? json_decode($payment->purchased_items, true) : []);

            Log::info('Admin Dashboard: purchased_items (raw) before aggregation', [
                'payment_id' => $payment->id,
                'items' => $items,
            ]);

            if (!empty($items)) {
                foreach ($items as $item) {
                    $totalPurchasedItems += (int) ($item['quantity'] ?? 1);
                }
            }
        }

        // Monthly spending trend (last 6 months) - ALL completed
        $monthlySpending = Payment::whereRaw('LOWER(TRIM(status)) = ?', ['completed'])
            ->where('payment_date', '>=', Carbon::now()->subMonths(6))
            ->selectRaw('MONTH(payment_date) as month, YEAR(payment_date) as year, SUM(amount) as total')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'period' => Carbon::createFromDate($item->year, $item->month, 1)->format('M Y'),
                    'amount' => (float) $item->total,
                ];
            });

        Log::info('Admin Dashboard: monthly spending snapshot', [
            'data' => $monthlySpending->toArray(),
        ]);

        // Recent completed payments (admin-wide)
        $recentPayments = $completedPayments->sortByDesc('payment_date')->take(5)->values();

        // Product and category analytics from completed payments
        $purchasedProducts = collect();
        $categorySpending = collect();

        foreach ($completedPayments as $payment) {
            $items = is_array($payment->purchased_items)
                ? $payment->purchased_items
                : (is_string($payment->purchased_items) ? json_decode($payment->purchased_items, true) : []);

            if (empty($items)) {
                continue;
            }

            foreach ($items as $item) {
                $productId = $item['product_id'] ?? null;
                if (!$productId) {
                    continue;
                }

                $product = Product::with('category')->find($productId);
                $categoryName = $product && $product->category ? $product->category->name : 'Unknown';

                $quantity = (int) ($item['quantity'] ?? 1);
                $price = (float) ($item['price'] ?? 0);
                $totalSpent = $price * $quantity;

                $existingIndex = $purchasedProducts->search(fn ($p) => $p['id'] === $productId);
                if ($existingIndex !== false) {
                    $purchasedProducts[$existingIndex]['quantity'] += $quantity;
                    $purchasedProducts[$existingIndex]['total_spent'] += $totalSpent;
                    if ($payment->payment_date > $purchasedProducts[$existingIndex]['last_purchased']) {
                        $purchasedProducts[$existingIndex]['last_purchased'] = $payment->payment_date;
                    }
                } else {
                    // Use canonical product data (same as products.index)
                    $purchasedProducts->push([
                        'id' => $productId,
                        'name' => $product?->name ?? 'Unknown Product',
                        'image' => $product?->image, // already a full /storage/... path
                        'category' => $categoryName,
                        'quantity' => $quantity,
                        'total_spent' => $totalSpent,
                        'last_purchased' => $payment->payment_date,
                    ]);
                }

                $categorySpending[$categoryName] = ($categorySpending[$categoryName] ?? 0) + $totalSpent;
            }
        }

        // Log computed metrics summary
        Log::info('Admin Dashboard: computed metrics', [
            'total_orders' => $totalOrders,
            'total_payments' => $totalPayments,
            'average_order_value' => $averageOrderValue,
            'total_purchased_items' => $totalPurchasedItems,
            'top_products_count' => $purchasedProducts->count(),
            'category_spending_keys' => array_keys($categorySpending->toArray()),
        ]);

        $topPurchasedProducts = $purchasedProducts->sortByDesc('quantity')->take(5);
        $topSpentProducts = $purchasedProducts->sortByDesc('total_spent')->take(5);
        $categorySpendingData = collect($categorySpending)->sortDesc()->take(5);

        // Purchase frequency (admin-wide)
        $firstPurchase = $completedPayments->min('payment_date');
        $daysSinceFirstPurchase = $firstPurchase ? Carbon::parse($firstPurchase)->diffInDays(now()) : 0;
        $purchaseFrequency = $daysSinceFirstPurchase > 0 ? round($totalOrders / ($daysSinceFirstPurchase / 30), 1) : 0;

        // Get categories with product counts
        $categoriesWithCounts = Category::withCount('products')->get();

        return view('home', compact(
            'totalProducts',
            'totalCategories',
            'cartItemsCount',
            'totalPayments',
            'totalOrders',
            'totalPurchasedItems',
            'averageOrderValue',
            'monthlySpending',
            'recentPayments',
            'categoriesWithCounts',
            'userCart',
            'topPurchasedProducts',
            'topSpentProducts',
            'categorySpendingData',
            'purchaseFrequency'
        ));
    }
}
