<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveLinkFromPromotionalAdvertisements extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('promotional_advertisements', 'link')) {
            Schema::table('promotional_advertisements', function (Blueprint $table) {
                $table->dropColumn('link');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('promotional_advertisements', 'link')) {
            Schema::table('promotional_advertisements', function (Blueprint $table) {
                $table->string('link')->nullable();
            });
        }
    }
}

