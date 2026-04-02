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
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('pending_delete')->default(false)->after('created_at');
            $table->unsignedBigInteger('requested_by')->nullable()->after('pending_delete');
            $table->timestamp('delete_requested_at')->nullable()->after('requested_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['pending_delete', 'requested_by', 'delete_requested_at']);
        });
    }
};
