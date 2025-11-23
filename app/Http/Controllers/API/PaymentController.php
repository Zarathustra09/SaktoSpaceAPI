<?php

    namespace App\Http\Controllers\API;

    use App\Http\Controllers\Controller;
    use App\Models\Cart;
    use App\Models\Payment;
    use App\Models\Product;
    use App\Models\Order;
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
           $validator = Validator::make($request->all(), [
               'payment_method' => 'required|string',
               'billing_address' => 'required|string',
               'shipping_address' => 'required|string',
               'recipient_name' => 'nullable|string',
               'recipient_contact' => 'nullable|string',
               'shipping_fee' => 'nullable|numeric|min:0',
               'metadata' => 'nullable|array',
               'metadata.shipping_type' => 'nullable|string',
               'metadata.free_shipping' => 'nullable|boolean',
               'metadata.gcash_number' => 'required_if:payment_method,GCash|nullable|string',
               'metadata.gcash_reference' => 'required_if:payment_method,GCash|nullable|string',
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

               $transactionId = 'SKTO-' . mt_rand(100000000, 999999999);
               $subtotal = 0;
               $orderItems = [];

               // Process cart items and calculate subtotal
               foreach ($cart->items as $item) {
                   $product = Product::find($item['product_id']);
                   if ($product) {
                       if ($product->stock < $item['quantity']) {
                           DB::rollBack();
                           return response()->json([
                               'success' => false,
                               'message' => "Insufficient stock for product: {$product->name}"
                           ], 400);
                       }

                       $itemSubtotal = $product->price * $item['quantity'];
                       $subtotal += $itemSubtotal;

                       $orderItems[] = [
                           'product_id' => $product->id,
                           'quantity' => $item['quantity'],
                           'price' => $product->price,
                           'subtotal' => $itemSubtotal,
                           'product_name' => $product->name,
                           'category_id' => $product->category_id,
                           'purchased_at' => now(),
                           'status' => Order::STATUS_PREPARING,
                           'status_updated_at' => now(),
                       ];

                       // Decrease product stock
                       $product->stock -= $item['quantity'];
                       $product->save();
                   }
               }

               // Calculate total with shipping fee
               $shippingFee = $request->shipping_fee ?? 0.00;
               $totalAmount = $subtotal + $shippingFee;

               // Determine payment status based on payment method
               $paymentStatus = Payment::STATUS_COMPLETED;
               if (strtolower($request->payment_method) === 'gcash') {
                   $paymentStatus = Payment::STATUS_PENDING;
               }

               // Create the payment record
               $payment = Payment::create([
                   'user_id' => $cart->user_id,
                   'amount' => $subtotal,
                   'shipping_fee' => $shippingFee,
                   'payment_method' => $request->payment_method,
                   'transaction_id' => $transactionId,
                   'status' => $paymentStatus,
                   'billing_address' => $request->billing_address,
                   'shipping_address' => $request->shipping_address,
                   'recipient_name' => $request->recipient_name ?? null,
                   'recipient_contact' => $request->recipient_contact ?? null,
                   'payment_date' => now(),
                   'metadata' => $request->metadata ?? null,
               ]);

               // Create order records
               foreach ($orderItems as $orderItem) {
                   $orderItem['payment_id'] = $payment->id;
                   Order::create($orderItem);
               }

               // Clear the cart after successful payment
               $cart->items = [];
               $cart->save();

               DB::commit();

               // Load orders for response
               $payment->load('orders');

               return response()->json([
                   'success' => true,
                   'message' => 'Payment processed successfully',
                   'data' => [
                       'payment_id' => $payment->id,
                       'transaction_id' => $transactionId,
                       'amount' => $payment->amount,
                       'shipping_fee' => $payment->shipping_fee,
                       'total_amount' => $totalAmount,
                       'payment_method' => $payment->payment_method,
                       'status' => $payment->status,
                       'recipient_name' => $payment->recipient_name,
                       'recipient_contact' => $payment->recipient_contact,
                       'metadata' => $payment->metadata,
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
                'recipient_name' => 'nullable|string',
                'recipient_contact' => 'nullable|string',
                'shipping_fee' => 'nullable|numeric|min:0',
                'metadata' => 'nullable|array',
                'metadata.shipping_type' => 'nullable|string',
                'metadata.free_shipping' => 'nullable|boolean',
                'metadata.gcash_number' => 'required_if:payment_method,GCash|nullable|string',
                'metadata.gcash_reference' => 'required_if:payment_method,GCash|nullable|string',
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

                if ($product->stock < $request->quantity) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for product: {$product->name}. Available: {$product->stock}"
                    ], 400);
                }

                $transactionId = 'SKTO-' . mt_rand(100000000, 999999999);
                $subtotal = $product->price * $request->quantity;

                // Calculate total with shipping fee
                $shippingFee = $request->shipping_fee ?? 0.00;
                $totalAmount = $subtotal + $shippingFee;

                // Determine payment status based on payment method
                $paymentStatus = Payment::STATUS_COMPLETED;
                if (strtolower($request->payment_method) === 'gcash') {
                    $paymentStatus = Payment::STATUS_PENDING;
                }

                // Create the payment record
                $payment = Payment::create([
                    'user_id' => Auth::id(),
                    'amount' => $subtotal,
                    'shipping_fee' => $shippingFee,
                    'payment_method' => $request->payment_method,
                    'transaction_id' => $transactionId,
                    'status' => $paymentStatus,
                    'billing_address' => $request->billing_address,
                    'shipping_address' => $request->shipping_address,
                    'recipient_name' => $request->recipient_name ?? null,
                    'recipient_contact' => $request->recipient_contact ?? null,
                    'payment_date' => now(),
                    'metadata' => $request->metadata ?? null,
                ]);

                // Create order record
                Order::create([
                    'payment_id' => $payment->id,
                    'product_id' => $product->id,
                    'quantity' => $request->quantity,
                    'price' => $product->price,
                    'subtotal' => $subtotal,
                    'product_name' => $product->name,
                    'category_id' => $product->category_id,
                    'purchased_at' => now(),
                    'status' => Order::STATUS_PREPARING,
                    'status_updated_at' => now(),
                ]);

                // Decrease product stock
                $product->stock -= $request->quantity;
                $product->save();

                DB::commit();

                // Load orders for response
                $payment->load('orders');

                return response()->json([
                    'success' => true,
                    'message' => 'Direct payment processed successfully',
                    'data' => [
                        'payment_id' => $payment->id,
                        'transaction_id' => $transactionId,
                        'amount' => $payment->amount,
                        'shipping_fee' => $payment->shipping_fee,
                        'total_amount' => $totalAmount,
                        'payment_method' => $payment->payment_method,
                        'status' => $payment->status,
                        'recipient_name' => $payment->recipient_name,
                        'recipient_contact' => $payment->recipient_contact,
                        'metadata' => $payment->metadata,
                        'product' => [
                            'name' => $product->name,
                            'quantity' => $request->quantity,
                            'unit_price' => $product->price,
                            'total_price' => $subtotal
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
