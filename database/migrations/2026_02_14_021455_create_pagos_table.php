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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->enum('metodo', ['STRIPE','PAYPAL','TRANSFERENCIA']);
            $table->string('referencia', 50);
            $table->decimal('monto', 10, 2);
            $table->enum('estado', ['PENDIENTE', 'APROBADO','RECHAZADO'])->default('PENDIENTE');
            $table->json('respuesta_pasarela')->nullable(); 
            $table->unsignedBigInteger('order_id');
            $table->foreign('order_id')->references('id')->on('orders');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
