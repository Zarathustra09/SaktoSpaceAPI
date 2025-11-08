<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', [
                'Preparing',
                'To Ship',
                'In Transit',
                'Out for Delivery',
                'Delivered',
                'Cancelled'
            ])->default('Preparing')->after('purchased_at');

            $table->timestamp('status_updated_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['status', 'status_updated_at']);
        });
    }
};

