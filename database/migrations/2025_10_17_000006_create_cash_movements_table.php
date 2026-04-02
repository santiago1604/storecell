<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cash_session_id');
            $table->string('type'); // ingreso, egreso
            $table->decimal('amount', 10, 2);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('cash_session_id')->references('id')->on('cash_sessions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
