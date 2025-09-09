<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Display a listing of the payments.
     */
    public function index(): View
    {
        $payments = Payment::with('user')->orderBy('payment_date', 'desc')->get();
        return view('payments.index', compact('payments'));
    }

    /**
     * Display the specified payment.
     */
    public function show(Payment $payment): JsonResponse
    {
        $payment->load('user');
        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }

    /**
     * Update the specified payment status.
     */
    public function update(Request $request, Payment $payment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,completed,failed,refunded',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $payment->update([
            'status' => $request->status
        ]);

        $payment->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Payment status updated successfully.',
            'data' => $payment
        ]);
    }
}
