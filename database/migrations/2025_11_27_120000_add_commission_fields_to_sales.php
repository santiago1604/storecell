<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('gateway_type')->nullable()->after('sale_number'); // 'bold' | 'sistecredito' | null
            $table->decimal('commission_amount', 10, 2)->default(0)->after('total');
            $table->string('commission_method')->nullable()->after('commission_amount'); // 'cash' | 'virtual' | null
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['gateway_type', 'commission_amount', 'commission_method']);
        });
    }
};
