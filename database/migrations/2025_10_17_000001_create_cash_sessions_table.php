<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->decimal('base_amount', 10, 2)->default(0);
            $table->unsignedBigInteger('opened_by')->nullable();
            $table->timestamp('open_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('close_at')->nullable();
            $table->timestamps();

            $table->foreign('opened_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('closed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_sessions');
    }
};
