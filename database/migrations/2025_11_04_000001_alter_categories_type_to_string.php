<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Convert ENUM to VARCHAR(255) to allow dynamic types
            $table->string('type')->change();
        });
    }

    public function down(): void
    {
        // Revert if needed (may fail if existing values are not in this list)
        Schema::table('categories', function (Blueprint $table) {
            $table->enum('type', ['furniture', 'decor', 'lighting', 'outdoor', 'appliance'])->change();
        });
    }
};

