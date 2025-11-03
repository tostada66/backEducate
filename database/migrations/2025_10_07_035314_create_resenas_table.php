<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('resenas', function (Blueprint $table) {
            $table->bigIncrements('idresena');

            // 🔗 Relaciones principales
            $table->unsignedBigInteger('idestudiante');
            $table->unsignedBigInteger('idcurso');

            // ✍️ Contenido de la reseña
            $table->tinyInteger('puntuacion')->unsigned()->comment('Puntuación del 1 al 5');
            $table->text('comentario')->nullable();

            // 📅 Fechas
            $table->timestamps();

            // 🔗 Llaves foráneas
            $table->foreign('idestudiante')
                ->references('idestudiante')
                ->on('estudiantes')
                ->cascadeOnDelete();

            $table->foreign('idcurso')
                ->references('idcurso')
                ->on('cursos')
                ->cascadeOnDelete();

            // ⚙️ Restricción: un estudiante solo puede dejar una reseña por curso
            $table->unique(['idestudiante', 'idcurso']);

            // 🔍 Índices para búsqueda
            $table->index(['idcurso', 'puntuacion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resenas');
    }
};
