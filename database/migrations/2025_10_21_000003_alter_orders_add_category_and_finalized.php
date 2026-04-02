<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->after('quantity');
            $table->foreign('category_id')->references('id')->on('categories');
            $table->enum('finalized', ['no','si'])->default('no')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['category_id','finalized']);
        });
    }
};
