<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('cursos', function (Blueprint $table) {
            $table->bigIncrements('idcurso');

            // 🔗 Relaciones principales
            $table->unsignedBigInteger('idprofesor');
            $table->unsignedBigInteger('idcategoria');

            // 📚 Datos del curso
            $table->string('nombre', 150);
            $table->string('slug', 180)->unique();
            $table->enum('nivel', ['Básico', 'Intermedio', 'Avanzado'])->nullable();
            $table->text('descripcion')->nullable();
            $table->string('imagen', 255)->nullable();

            // ⭐ Estadísticas de reseñas
            $table->float('promedio_resenas', 3, 2)->default(0)->comment('Promedio de puntuación de reseñas');
            $table->unsignedInteger('total_resenas')->default(0)->comment('Número total de reseñas del curso');

            // ⏱️ Duración total del curso (suma de unidades y clases)
            $table->unsignedInteger('duracion_total')->default(0);

            // ⚙️ Estado del curso y timestamps
            $table->enum('estado', [
                'borrador',
                'en_revision',
                'oferta_enviada',
                'pendiente_aceptacion',
                'publicado',
                'rechazado',
                'archivado'
            ])->default('borrador');

            $table->timestamps();
            $table->softDeletes();

            // 🔗 Llaves foráneas
            $table->foreign('idprofesor')
                ->references('idprofesor')
                ->on('profesores')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('idcategoria')
                ->references('idcategoria')
                ->on('categorias')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // 🔍 Índices recomendados
            $table->index(['idprofesor', 'idcategoria']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cursos');
    }
};
