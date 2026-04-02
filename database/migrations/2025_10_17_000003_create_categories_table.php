<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Esta migración es duplicada respecto a 2025_10_17_000001_create_categories_table.
        // Evitamos el error "table already exists" haciéndola idempotente.
        if (Schema::hasTable('categories')) {
            return;
        }
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
