<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('unidades', function (Blueprint $table) {
            $table->bigIncrements('idunidad');
            $table->unsignedBigInteger('idcurso'); // FK hacia cursos

            // 📚 Datos de la unidad
            $table->string('titulo', 180);
            $table->text('descripcion')->nullable();
            $table->text('objetivos')->nullable();

            // 🖼 Imagen (opcional)
            $table->string('imagen', 255)->nullable();

            // ⏱ Duración estimada (puede quedar)
            $table->integer('duracion_estimada')->nullable();

            // ⏱ Duración total real (suma de las clases)
            $table->unsignedInteger('duracion_total')->default(0);

            // ⚙️ Estado (igual que cursos)
            $table->enum('estado', [
                'borrador',
                'en_revision',
                'oferta_enviada',
                'pendiente_aceptacion',
                'publicado',
                'rechazado',
                'archivado'
            ])->default('borrador');

            // 🕒 Timestamps y soft delete
            $table->timestamps();
            $table->softDeletes();

            // 🔗 Relación con cursos
            $table->foreign('idcurso')
                  ->references('idcurso')
                  ->on('cursos')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unidades');
    }
};
