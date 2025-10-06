<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('licencias', function (Blueprint $table) {
            $table->bigIncrements('idlicencia');

            // 🔗 Relaciones
            $table->unsignedBigInteger('idcurso');
            $table->unsignedBigInteger('idprofesor');

            // 📊 Datos de la licencia
            $table->integer('num_clases');                // total de clases del curso
            $table->decimal('tarifa_por_clase', 10, 2);   // configurable
            $table->integer('duracion_meses');            // duración definida al aprobar
            $table->decimal('costo', 10, 2);              // calculado

            $table->date('fechainicio');
            $table->date('fechafin');

            $table->enum('estado', ['activa','vencida','rechazada'])->default('activa');

            $table->timestamps();

            // FK
            $table->foreign('idcurso')
                  ->references('idcurso')->on('cursos')
                  ->cascadeOnDelete();

            $table->foreign('idprofesor')
                  ->references('idprofesor')->on('profesores')
                  ->restrictOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('licencias');
    }
};
