<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrdersController extends Controller
{
    /**
     * Get all orders for the authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $orders = Order::whereHas('payment', function ($query) {
            $query->where('user_id', Auth::id());
        })
            ->with(['payment', 'product', 'category'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'payment_id' => $order->payment_id,
                    'transaction_id' => $order->payment->transaction_id,
                    'product_id' => $order->product_id,
                    'product_name' => $order->product_name,
                    'quantity' => $order->quantity,
                    'price' => $order->price,
                    'subtotal' => $order->subtotal,
                    'status' => $order->status,
                    'status_updated_at' => $order->status_updated_at,
                    'category_id' => $order->category_id,
                    'category_name' => $order->category?->name ?? 'Unknown',
                    'product' => $order->product,
                    'payment_status' => $order->payment->status,
                    'payment_method' => $order->payment->payment_method,
                    'shipping_fee' => $order->payment->shipping_fee,
                    'metadata' => $order->payment->metadata,
                    'recipient_name' => $order->payment->recipient_name,
                    'recipient_contact' => $order->payment->recipient_contact,
                    'purchased_at' => $order->purchased_at,
                    'created_at' => $order->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Get orders grouped by payment for the authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrdersByPayment()
    {
        $payments = Payment::where('user_id', Auth::id())
            ->with(['orders.product', 'orders.category'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'payment_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'total_amount' => $payment->amount,
                    'shipping_fee' => $payment->shipping_fee,
                    'metadata' => $payment->metadata,
                    'status' => $payment->status,
                    'payment_method' => $payment->payment_method,
                    'billing_address' => $payment->billing_address,
                    'shipping_address' => $payment->shipping_address,
                    'recipient_name' => $payment->recipient_name,
                    'recipient_contact' => $payment->recipient_contact,
                    'payment_date' => $payment->payment_date,
                    'orders' => $payment->orders->map(function ($order) {
                        return [
                            'id' => $order->id,
                            'product_id' => $order->product_id,
                            'product_name' => $order->product_name,
                            'quantity' => $order->quantity,
                            'price' => $order->price,
                            'subtotal' => $order->subtotal,
                            'status' => $order->status,
                            'status_updated_at' => $order->status_updated_at,
                            'category_id' => $order->category_id,
                            'category_name' => $order->category?->name ?? 'Unknown',
                            'product' => $order->product,
                            'purchased_at' => $order->purchased_at,
                        ];
                    }),
                    'created_at' => $payment->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    /**
     * Get a specific order for the authenticated user
     *
     * @param  int  $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($orderId)
    {
        $order = Order::whereHas('payment', function ($query) {
            $query->where('user_id', Auth::id());
        })
            ->with(['payment', 'product', 'category'])
            ->find($orderId);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $orderData = [
            'id' => $order->id,
            'payment_id' => $order->payment_id,
            'transaction_id' => $order->payment->transaction_id,
            'product_id' => $order->product_id,
            'product_name' => $order->product_name,
            'quantity' => $order->quantity,
            'price' => $order->price,
            'subtotal' => $order->subtotal,
            'status' => $order->status,
            'status_updated_at' => $order->status_updated_at,
            'category_id' => $order->category_id,
            'category_name' => $order->category?->name ?? 'Unknown',
            'product' => $order->product,
            'payment_status' => $order->payment->status,
            'payment_method' => $order->payment->payment_method,
            'shipping_fee' => $order->payment->shipping_fee,
            'metadata' => $order->payment->metadata,
            'billing_address' => $order->payment->billing_address,
            'shipping_address' => $order->payment->shipping_address,
            'recipient_name' => $order->payment->recipient_name,
            'recipient_contact' => $order->payment->recipient_contact,
            'purchased_at' => $order->purchased_at,
            'created_at' => $order->created_at,
        ];

        return response()->json([
            'success' => true,
            'data' => $orderData,
        ]);
    }

    /**
     * Update order status (Admin only)
     *
     * @param  int  $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $orderId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:'.implode(',', Order::getStatuses()),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = Order::find($orderId);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $order->update([
            'status' => $request->status,
            'status_updated_at' => now(),
        ]);

        $order->load(['payment.user', 'product', 'category']);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => [
                'id' => $order->id,
                'status' => $order->status,
                'status_updated_at' => $order->status_updated_at,
                'product_name' => $order->product_name,
                'transaction_id' => $order->payment->transaction_id,
            ],
        ]);
    }

    /**
     * Get orders by status
     *
     * @param  string  $status
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrdersByStatus($status)
    {
        if (! in_array($status, Order::getStatuses())) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status',
            ], 400);
        }

        $orders = Order::whereHas('payment', function ($query) {
            $query->where('user_id', Auth::id());
        })
            ->where('status', $status)
            ->with(['payment', 'product', 'category'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'payment_id' => $order->payment_id,
                    'transaction_id' => $order->payment->transaction_id,
                    'product_id' => $order->product_id,
                    'product_name' => $order->product_name,
                    'quantity' => $order->quantity,
                    'price' => $order->price,
                    'subtotal' => $order->subtotal,
                    'status' => $order->status,
                    'status_updated_at' => $order->status_updated_at,
                    'category_id' => $order->category_id,
                    'category_name' => $order->category?->name ?? 'Unknown',
                    'product' => $order->product,
                    'payment_status' => $order->payment->status,
                    'payment_method' => $order->payment->payment_method,
                    'shipping_fee' => $order->payment->shipping_fee,
                    'metadata' => $order->payment->metadata,
                    'recipient_name' => $order->payment->recipient_name,
                    'recipient_contact' => $order->payment->recipient_contact,
                    'purchased_at' => $order->purchased_at,
                    'created_at' => $order->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Get available order statuses
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatuses()
    {
        return response()->json([
            'success' => true,
            'data' => Order::getStatuses(),
        ]);
    }

    /**
     * Get a specific payment with all its orders for the authenticated user
     *
     * @param  int  $paymentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function showPayment($paymentId)
    {
        $payment = Payment::where('id', $paymentId)
            ->where('user_id', Auth::id())
            ->with(['orders.product', 'orders.category'])
            ->first();

        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        $paymentData = [
            'payment_id' => $payment->id,
            'transaction_id' => $payment->transaction_id,
            'total_amount' => $payment->amount,
            'shipping_fee' => $payment->shipping_fee,
            'metadata' => $payment->metadata,
            'status' => $payment->status,
            'payment_method' => $payment->payment_method,
            'billing_address' => $payment->billing_address,
            'shipping_address' => $payment->shipping_address,
            'recipient_name' => $payment->recipient_name,
            'recipient_contact' => $payment->recipient_contact,
            'payment_date' => $payment->payment_date,
            'orders' => $payment->orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'product_id' => $order->product_id,
                    'product_name' => $order->product_name,
                    'quantity' => $order->quantity,
                    'price' => $order->price,
                    'subtotal' => $order->subtotal,
                    'category_id' => $order->category_id,
                    'category_name' => $order->category?->name ?? 'Unknown',
                    'product' => $order->product,
                    'purchased_at' => $order->purchased_at,
                ];
            }),
            'created_at' => $payment->created_at,
        ];

        return response()->json([
            'success' => true,
            'data' => $paymentData,
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
        $orders = Order::whereHas('payment', function ($query) {
            $query->where('user_id', Auth::id());
        })->get();

        $statusCounts = [];
        foreach (Order::getStatuses() as $status) {
            $statusCounts[$status] = $orders->where('status', $status)->count();
        }

        $paymentStatusCounts = [];
        foreach (Payment::getStatuses() as $status) {
            $paymentStatusCounts[$status] = $payments->where('status', $status)->count();
        }

        $stats = [
            'total_payments' => $payments->count(),
            'total_orders' => $orders->count(),
            'total_spent' => $payments->where('status', Payment::STATUS_COMPLETED)->sum('amount'),
            'completed_payments' => $payments->where('status', Payment::STATUS_COMPLETED)->count(),
            'pending_payments' => $payments->where('status', Payment::STATUS_PENDING)->count(),
            'cancelled_payments' => $payments->where('status', Payment::STATUS_CANCELLED)->count(),
            'refunded_payments' => $payments->where('status', Payment::STATUS_REFUNDED)->count(),
            'total_items_purchased' => $orders->sum('quantity'),
            'average_order_value' => $payments->where('status', Payment::STATUS_COMPLETED)->count() > 0
                ? $payments->where('status', Payment::STATUS_COMPLETED)->avg('amount')
                : 0,
            'orders_by_status' => $statusCounts,
            'payments_by_status' => $paymentStatusCounts,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get orders by product for the authenticated user
     *
     * @param  int  $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrdersByProduct($productId)
    {
        $orders = Order::whereHas('payment', function ($query) {
            $query->where('user_id', Auth::id());
        })
            ->where('product_id', $productId)
            ->with(['payment', 'product', 'category'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'payment_id' => $order->payment_id,
                    'transaction_id' => $order->payment->transaction_id,
                    'quantity' => $order->quantity,
                    'price' => $order->price,
                    'subtotal' => $order->subtotal,
                    'payment_status' => $order->payment->status,
                    'purchased_at' => $order->purchased_at,
                    'created_at' => $order->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }
}
