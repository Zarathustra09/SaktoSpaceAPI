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
        if (!Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'recipient_name')) {
                $table->string('recipient_name')->nullable()->after('shipping_address');
            }
            if (!Schema::hasColumn('payments', 'recipient_contact')) {
                $table->string('recipient_contact')->nullable()->after('recipient_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'recipient_contact')) {
                $table->dropColumn('recipient_contact');
            }
            if (Schema::hasColumn('payments', 'recipient_name')) {
                $table->dropColumn('recipient_name');
            }
        });
    }
};
