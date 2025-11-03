<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('matriculas', function (Blueprint $table) {
            $table->bigIncrements('idmatricula');
            $table->unsignedBigInteger('idestudiante');
            $table->unsignedBigInteger('idcurso');
            $table->date('fecha')->nullable();

            // 🔹 Estado general de la matrícula
            $table->enum('estado', ['activa', 'completada', 'cancelada'])->default('activa');

            // 📊 Nuevo campo: porcentaje global de avance (0 a 100)
            $table->decimal('porcentaje_avance', 5, 2)->default(0)
                  ->comment('Porcentaje de progreso total del curso (0-100)');

            $table->timestamps();

            // 🔗 Relaciones
            $table->foreign('idestudiante')
                  ->references('idestudiante')
                  ->on('estudiantes')
                  ->cascadeOnDelete();

            $table->foreign('idcurso')
                  ->references('idcurso')
                  ->on('cursos')
                  ->cascadeOnDelete();

            $table->unique(['idestudiante', 'idcurso']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matriculas');
    }
};
