<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->bigIncrements('idfactura');

            // 🧑 Usuario asociado (puede ser estudiante o profesor)
            $table->unsignedBigInteger('idusuario');

            // 📘 Tipo de factura
            $table->enum('tipo', ['suscripcion', 'licencia', 'pago_profesor'])
                ->default('suscripcion')
                ->comment('Define si la factura es por suscripción, licencia o pago a profesor');

            // 🔗 Relaciones condicionales
            $table->unsignedBigInteger('idplan')->nullable();           // si tipo = suscripcion
            $table->unsignedBigInteger('idlicencia')->nullable();       // si tipo = licencia o pago_profesor
            $table->unsignedBigInteger('idtipo_pago')->nullable();      // si tipo = suscripcion/licencia
            $table->unsignedBigInteger('idpago_profesor')->nullable();  // si tipo = pago_profesor

            // 💰 Datos de monto y moneda
            $table->decimal('total', 10, 2);
            $table->char('moneda', 3)->default('BOB');

            // 🧾 Datos de facturación
            $table->string('referencia', 100)->nullable();
            $table->string('nombre_factura', 150)->nullable();
            $table->string('nit', 20)->nullable();
            $table->string('razon_social', 120)->nullable();

            // 🕒 Estado y fechas
            $table->dateTime('fecha')->useCurrent();
            $table->enum('estado', ['pendiente', 'pagada', 'fallida', 'anulada'])->default('pagada');
            $table->string('pdf_path', 255)->nullable();

            $table->timestamps();

            // 🔗 Claves foráneas
            $table->foreign('idusuario')->references('idusuario')->on('usuarios')->cascadeOnDelete();
            $table->foreign('idplan')->references('idplan')->on('tipo_planes')->nullOnDelete();
            $table->foreign('idlicencia')->references('idlicencia')->on('licencias')->nullOnDelete();
            $table->foreign('idtipo_pago')->references('idtipo_pago')->on('tipos_pagos')->nullOnDelete();
            $table->foreign('idpago_profesor')->references('idpago')->on('pagos_profesores')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
