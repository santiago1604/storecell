<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repairs', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('device_description');
            $table->text('issue_description'); // Por qué dejó el dispositivo
            $table->text('repair_description')->nullable(); // Descripción del arreglo (técnico)
            $table->decimal('parts_cost', 10, 2)->nullable(); // Costo repuestos
            $table->decimal('total_cost', 10, 2)->nullable(); // Valor total reparación
            $table->foreignId('received_by')->constrained('users'); // Quien recibió
            $table->foreignId('technician_id')->nullable()->constrained('users'); // Técnico asignado
            $table->enum('status', ['pending', 'in_progress', 'completed', 'delivered'])->default('pending');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repairs');
    }
};
