<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('cash_session_id');
            $table->string('sale_number')->nullable();
            $table->decimal('total', 10, 2);
            $table->decimal('payment_cash', 10, 2)->default(0);
            $table->decimal('payment_virtual', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('cash_session_id')->references('id')->on('cash_sessions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
