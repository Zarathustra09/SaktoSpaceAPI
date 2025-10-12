<?php

    namespace App\Http\Controllers\API;

    use App\Http\Controllers\Controller;
    use App\Models\Cart;
    use App\Models\Payment;
    use App\Models\Product;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Validator;
    use Illuminate\Support\Facades\DB;

    class PaymentController extends Controller
    {
        /**
         * Process payment from cart
         *
         * @param Request $request
         * @return \Illuminate\Http\JsonResponse
         */
        public function processPayment(Request $request)
       {
           // In app/Http/Controllers/API/PaymentController.php
           $validator = Validator::make($request->all(), [
               'payment_method' => 'required|string',
               'billing_address' => 'required|string',
               'shipping_address' => 'required|string',
           ]);

           if ($validator->fails()) {
               return response()->json([
                   'success' => false,
                   'errors' => $validator->errors()
               ], 422);
           }

           $cart = Cart::where('user_id', Auth::id())->first();

           if (!$cart || empty($cart->items)) {
               return response()->json([
                   'success' => false,
                   'message' => 'Cart is empty'
               ], 400);
           }

           try {
               DB::beginTransaction();

               // Generate a random transaction ID with the format SKTO-123456789
               $transactionId = 'SKTO-' . mt_rand(100000000, 999999999);

               $purchasedItems = [];
               $totalAmount = 0;

               // Convert cart items to purchased items with additional details
               foreach ($cart->items as $item) {
                   $product = Product::find($item['product_id']);
                   if ($product) {
                       // Check if sufficient stock is available
                       if ($product->stock < $item['quantity']) {
                           DB::rollBack();
                           return response()->json([
                               'success' => false,
                               'message' => "Insufficient stock for product: {$product->name}"
                           ], 400);
                       }

                       $purchasedItem = [
                           'product_id' => $product->id,
                           'name' => $product->name,
                           'price' => $product->price,
                           'quantity' => $item['quantity'],
                           'subtotal' => $product->price * $item['quantity'],
                           'category_id' => $product->category_id,
                           'purchased_at' => now()->toDateTimeString(),
                       ];

                       $purchasedItems[] = $purchasedItem;
                       $totalAmount += $purchasedItem['subtotal'];

                       // Decrease product stock
                       $product->stock -= $item['quantity'];
                       $product->save();
                   }
               }

               // Create the payment record
               $payment = Payment::create([
                   'user_id' => $cart->user_id,
                   'order_id' => $request->order_id ?? null,
                   'amount' => $totalAmount,
                   'payment_method' => $request->payment_method,
                   'transaction_id' => $transactionId,
                   'status' => $request->status ?? 'completed',
                   'purchased_items' => $purchasedItems,
                   'billing_address' => $request->billing_address,
                   'shipping_address' => $request->shipping_address,
                   'payment_date' => now(),
               ]);

               // Clear the cart after successful payment
               $cart->items = [];
               $cart->save();

               DB::commit();

               return response()->json([
                   'success' => true,
                   'message' => 'Payment processed successfully',
                   'data' => [
                       'payment_id' => $payment->id,
                       'transaction_id' => $transactionId,
                       'amount' => $payment->amount,
                       'purchased_items' => $payment->purchased_items
                   ]
               ]);

           } catch (\Exception $e) {
               DB::rollBack();
               return response()->json([
                   'success' => false,
                   'message' => 'Payment processing failed',
                   'error' => $e->getMessage()
               ], 500);
           }
       }
        /**
         * Get payment details
         *
         * @param int $paymentId
         * @return \Illuminate\Http\JsonResponse
         */
        public function getPayment($paymentId)
        {
            $payment = Payment::where('id', $paymentId)
                ->where('user_id', Auth::id())
                ->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $payment
            ]);
        }

        /**
         * Get payment history for authenticated user
         *
         * @return \Illuminate\Http\JsonResponse
         */
        public function getPaymentHistory()
        {
            $payments = Payment::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $payments
            ]);
        }

        /**
         * Process direct payment for a product without cart
         *
         * @param Request $request
         * @return \Illuminate\Http\JsonResponse
         */
        public function processDirectPayment(Request $request)
        {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'payment_method' => 'required|string',
                'billing_address' => 'required|string',
                'shipping_address' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            try {
                DB::beginTransaction();

                $product = Product::find($request->product_id);

                if (!$product) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product not found'
                    ], 404);
                }

                // Check if sufficient stock is available
                if ($product->stock < $request->quantity) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for product: {$product->name}. Available: {$product->stock}"
                    ], 400);
                }

                // Generate a random transaction ID
                $transactionId = 'SKTO-' . mt_rand(100000000, 999999999);

                // Calculate total amount
                $totalAmount = $product->price * $request->quantity;

                // Create purchased item data
                $purchasedItem = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'quantity' => $request->quantity,
                    'subtotal' => $totalAmount,
                    'category_id' => $product->category_id,
                    'purchased_at' => now()->toDateTimeString(),
                ];

                // Create the payment record
                $payment = Payment::create([
                    'user_id' => Auth::id(),
                    'order_id' => $request->order_id ?? null,
                    'amount' => $totalAmount,
                    'payment_method' => $request->payment_method,
                    'transaction_id' => $transactionId,
                    'status' => $request->status ?? 'completed',
                    'purchased_items' => [$purchasedItem],
                    'billing_address' => $request->billing_address,
                    'shipping_address' => $request->shipping_address,
                    'payment_date' => now(),
                ]);

                // Decrease product stock
                $product->stock -= $request->quantity;
                $product->save();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Direct payment processed successfully',
                    'data' => [
                        'payment_id' => $payment->id,
                        'transaction_id' => $transactionId,
                        'amount' => $payment->amount,
                        'product' => [
                            'name' => $product->name,
                            'quantity' => $request->quantity,
                            'unit_price' => $product->price,
                            'total_price' => $totalAmount
                        ],
                        'purchased_items' => $payment->purchased_items
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Direct payment processing failed',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
    }
