<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'description')) {
                $table->string('description')->nullable()->after('product_id');
            }
        });

        // Hacer product_id nullable si no lo es
        if (Schema::hasColumn('sale_items', 'product_id')) {
            // Algunos motores requieren modificar en pasos separados
            Schema::table('sale_items', function (Blueprint $table) {
                $table->unsignedBigInteger('product_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'description')) {
                $table->dropColumn('description');
            }
        });
        // No forzamos volver product_id a NOT NULL para evitar pérdida de datos
    }
};
