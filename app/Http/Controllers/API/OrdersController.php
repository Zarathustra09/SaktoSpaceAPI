<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrdersController extends Controller
{
    /**
     * Get all orders (payments) for the authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $orders = Payment::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'order_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'total_amount' => $payment->amount,
                    'status' => $payment->status,
                    'payment_method' => $payment->payment_method,
                    'purchased_items' => $payment->purchased_items,
                    'billing_address' => $payment->billing_address,
                    'shipping_address' => $payment->shipping_address,
                    'order_date' => $payment->payment_date,
                    'created_at' => $payment->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Get a specific order (payment) for the authenticated user
     *
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($orderId)
    {
        $payment = Payment::where('id', $orderId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $order = [
            'order_id' => $payment->id,
            'transaction_id' => $payment->transaction_id,
            'total_amount' => $payment->amount,
            'status' => $payment->status,
            'payment_method' => $payment->payment_method,
            'purchased_items' => $payment->purchased_items,
            'billing_address' => $payment->billing_address,
            'shipping_address' => $payment->shipping_address,
            'order_date' => $payment->payment_date,
            'created_at' => $payment->created_at,
        ];

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Get order statistics for the authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderStats()
    {
        $payments = Payment::where('user_id', Auth::id())->get();

        $stats = [
            'total_orders' => $payments->count(),
            'total_spent' => $payments->sum('amount'),
            'completed_orders' => $payments->where('status', 'completed')->count(),
            'pending_orders' => $payments->where('status', 'pending')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
