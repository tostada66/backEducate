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
        Schema::create('facturas', function (Blueprint $table) {
            $table->bigIncrements('idfactura');
            $table->unsignedBigInteger('idusuario');              // usuarios.idusuario
            $table->enum('tipo', ['suscripcion','licencia']);
            $table->unsignedBigInteger('idplan')->nullable();     // si tipo = suscripcion
            $table->unsignedBigInteger('idlicencia')->nullable(); // si tipo = licencia
            $table->unsignedBigInteger('idpago');                 // tipo_pagos.idpago
            $table->decimal('total', 10, 2);
            $table->char('moneda', 3)->default('BOB');
            $table->string('referencia', 100)->nullable();
            $table->string('nit', 20)->nullable();
            $table->string('razon_social', 120)->nullable();
            $table->dateTime('fecha')->useCurrent();
            $table->enum('estado', ['pendiente','pagada','fallida','anulada'])->default('pagada');
            $table->string('pdf_path', 255)->nullable();
            $table->timestamps();

            $table->foreign('idusuario')->references('idusuario')->on('usuarios')->cascadeOnDelete();
            $table->foreign('idplan')->references('idplan')->on('tipo_planes')->nullOnDelete();
            $table->foreign('idlicencia')->references('idlicencia')->on('licencias')->nullOnDelete();
            $table->foreign('idpago')->references('idpago')->on('tipos_pagos')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};