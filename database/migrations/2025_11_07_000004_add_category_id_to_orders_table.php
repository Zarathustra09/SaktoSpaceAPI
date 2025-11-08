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
        if (!Schema::hasTable('orders')) {
            return;
        }

        if (!Schema::hasColumn('orders', 'category_id')) {
            Schema::table('orders', function (Blueprint $table) {
                // Add nullable foreign key to categories and set to null on delete
                $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        if (Schema::hasColumn('orders', 'category_id')) {
            Schema::table('orders', function (Blueprint $table) {
                // drop foreign key if exists then drop column
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $doctrineTable = $sm->listTableDetails($table->getTable());
                if ($doctrineTable->hasColumn('category_id')) {
                    // attempt to drop foreign key safely
                    if ($doctrineTable->hasForeignKey('orders_category_id_foreign')) {
                        $table->dropForeign(['category_id']);
                    } else {
                        // try generic dropForeign in case of different constraint name
                        try {
                            $table->dropForeign(['category_id']);
                        } catch (\Throwable $e) {
                            // ignore if unable to drop by name
                        }
                    }
                    $table->dropColumn('category_id');
                }
            });
        }
    }
};

