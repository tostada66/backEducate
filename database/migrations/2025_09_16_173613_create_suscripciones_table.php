<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('suscripciones', function (Blueprint $table) {
            $table->bigIncrements('idsus');
            $table->unsignedBigInteger('idestudiante');
            $table->unsignedBigInteger('idplan');
            $table->unsignedBigInteger('factura_id')->nullable(); // 👈 Nueva columna
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->boolean('estado')->default(true); // activa?
            $table->timestamps();

            // Relaciones
            $table->foreign('idestudiante')
                ->references('idestudiante')
                ->on('estudiantes')
                ->cascadeOnDelete();

            $table->foreign('idplan')
                ->references('idplan')
                ->on('tipo_planes')
                ->restrictOnDelete();

            $table->foreign('factura_id') // 👈 FK a facturas
                ->references('idfactura')
                ->on('facturas')
                ->nullOnDelete();

            $table->index(['idestudiante','estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suscripciones');
    }
};
