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
        Schema::table('repairs', function (Blueprint $table) {
            $table->boolean('is_warranty')->default(false)->after('delivered_at');
            $table->timestamp('warranty_returned_at')->nullable()->after('is_warranty');
            $table->text('warranty_notes')->nullable()->after('warranty_returned_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->dropColumn(['is_warranty', 'warranty_returned_at', 'warranty_notes']);
        });
    }
};
