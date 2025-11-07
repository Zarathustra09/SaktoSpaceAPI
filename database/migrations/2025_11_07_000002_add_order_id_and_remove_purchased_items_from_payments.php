<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Payment;
use App\Models\Order;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, migrate existing data from purchased_items to orders table
        $this->migrateExistingData();

        // Then modify the payments table
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('purchased_items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->json('purchased_items')->nullable();
        });

        // Optionally migrate data back from orders to purchased_items
        $this->migrateDataBack();
    }

    /**
     * Migrate existing purchased_items data to orders table
     */
    private function migrateExistingData(): void
    {
        $payments = Payment::whereNotNull('purchased_items')->get();

        foreach ($payments as $payment) {
            $purchasedItems = is_string($payment->purchased_items)
                ? json_decode($payment->purchased_items, true)
                : $payment->purchased_items;

            if (is_array($purchasedItems)) {
                foreach ($purchasedItems as $item) {
                    Order::create([
                        'payment_id' => $payment->id,
                        'product_id' => $item['product_id'] ?? null,
                        'quantity' => $item['quantity'] ?? 1,
                        'price' => $item['price'] ?? 0,
                        'subtotal' => $item['subtotal'] ?? 0,
                        'product_name' => $item['name'] ?? 'Unknown Product',
                        'category_id' => $item['category_id'] ?? null,
                        'purchased_at' => isset($item['purchased_at'])
                            ? \Carbon\Carbon::parse($item['purchased_at'])
                            : $payment->payment_date,
                    ]);
                }
            }
        }
    }

    /**
     * Migrate data back from orders to purchased_items (for rollback)
     */
    private function migrateDataBack(): void
    {
        $payments = Payment::with('orders')->get();

        foreach ($payments as $payment) {
            $purchasedItems = [];

            foreach ($payment->orders as $order) {
                $purchasedItems[] = [
                    'product_id' => $order->product_id,
                    'name' => $order->product_name,
                    'price' => (float) $order->price,
                    'quantity' => $order->quantity,
                    'subtotal' => (float) $order->subtotal,
                    'category_id' => $order->category_id,
                    'purchased_at' => $order->purchased_at->toDateTimeString(),
                ];
            }

            $payment->update(['purchased_items' => $purchasedItems]);
        }
    }
};


