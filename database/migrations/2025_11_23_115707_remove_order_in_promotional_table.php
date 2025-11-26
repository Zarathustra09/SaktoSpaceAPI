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
        if (Schema::hasColumn('promotional_advertisements', 'display_order')) {
            Schema::table('promotional_advertisements', function (Blueprint $table) {
                $table->dropColumn('display_order');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('promotional_advertisements', 'display_order')) {
            Schema::table('promotional_advertisements', function (Blueprint $table) {
                $table->unsignedInteger('display_order')->default(0);
            });
        }
    }
};
