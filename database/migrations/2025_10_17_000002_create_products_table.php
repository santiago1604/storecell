<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('description');
            $table->integer('stock_qty')->default(0);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->decimal('sale_price', 10, 2)->default(0);
            $table->string('barcode')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
