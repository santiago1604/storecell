<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'unit_price')) {
                $table->decimal('unit_price', 10, 2)->after('quantity');
            }
            if (!Schema::hasColumn('sale_items', 'subtotal')) {
                $table->decimal('subtotal', 10, 2)->after('unit_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'subtotal')) {
                $table->dropColumn('subtotal');
            }
            if (Schema::hasColumn('sale_items', 'unit_price')) {
                $table->dropColumn('unit_price');
            }
        });
    }
};
