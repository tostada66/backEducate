<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ofertas', function (Blueprint $table) {
            $table->bigIncrements('idoferta');

            // 🔗 Relaciones
            $table->unsignedBigInteger('idcurso');
            $table->unsignedBigInteger('idprofesor');

            // 📊 Datos de la oferta
            $table->integer('num_clases')->default(0);
            $table->decimal('tarifa_por_clase', 10, 2);
            $table->decimal('tarifa_por_mes', 10, 2)->default(0);
            $table->integer('duracion_meses')->default(1);
            $table->decimal('costo_total', 12, 2);

            // 🧩 Estado
            $table->enum('estado', ['pendiente', 'aceptada', 'rechazada'])->default('pendiente');

            $table->timestamps();

            // 🔗 Claves foráneas
            $table->foreign('idcurso')
                  ->references('idcurso')->on('cursos')
                  ->cascadeOnDelete();

            $table->foreign('idprofesor')
                  ->references('idprofesor')->on('profesores')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('ofertas');
    }
};
