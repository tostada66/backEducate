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

            // ⏱ Duración estimada (suma de clases de la unidad)
            $table->integer('duracion_estimada')->nullable();

            // ⚙️ Estado (mismos valores que cursos)
            $table->enum('estado', [
                'borrador',              // Recién creada
                'en_revision',           // En revisión por admin
                'oferta_enviada',        // Oferta enviada
                'pendiente_aceptacion',  // Esperando decisión del profesor
                'publicado',             // Activa y visible
                'rechazado',             // Rechazada
                'archivado'              // No disponible
            ])->default('borrador');

            // 🕒 Timestamps y soft delete
            $table->timestamps();
            $table->softDeletes();

            // 🔗 Relación con cursos
            $table->foreign('idcurso')
                  ->references('idcurso')->on('cursos')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unidades');
    }
};
