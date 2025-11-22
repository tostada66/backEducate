<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Ejecuta las migraciones.
     */
    public function up(): void
    {
        Schema::create('pagos_profesores', function (Blueprint $table) {
            $table->bigIncrements('idpago');

            // 🔗 Relaciones
            $table->unsignedBigInteger('idprofesor');
            $table->unsignedBigInteger('idlicencia');

            // 💰 Datos del pago
            $table->decimal('monto', 12, 2);
            $table->enum('estado', ['pendiente', 'pagado', 'cancelado'])->default('pendiente');
            $table->string('metodo_pago', 100)->nullable()->comment('transferencia, QR, efectivo, etc.');
            $table->string('referencia', 100)->nullable()->comment('Código o número de transacción');
            $table->timestamp('fecha_generacion')->useCurrent();
            $table->timestamp('fecha_pago')->nullable();

            $table->timestamps();

            // 🔗 Claves foráneas
            $table->foreign('idprofesor')
                ->references('idprofesor')->on('profesores')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('idlicencia')
                ->references('idlicencia')->on('licencias')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // 🔍 Índices
            $table->index(['idprofesor', 'estado']);
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos_profesores');
    }
};
