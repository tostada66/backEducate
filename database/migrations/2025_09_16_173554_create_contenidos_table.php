<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('contenidos', function (Blueprint $table) {
            $table->bigIncrements('idcontenido');
            $table->unsignedBigInteger('idclase');

            // 📘 Datos principales
            $table->string('titulo', 180);
            $table->text('descripcion')->nullable();

            // 🎥 Tipo de contenido: texto, video, pdf, link, quiz, etc.
            $table->string('tipo', 50)->default('texto');

            // 📁 Ruta del archivo o URL externa
            $table->string('url', 255)->nullable();

            // 🖼 Miniatura (para videos)
            $table->string('miniatura', 255)->nullable();

            // ⏱ Duración en segundos (videos)
            $table->unsignedInteger('duracion')->nullable();

            // 🔢 Orden de aparición en la clase
            $table->integer('orden')->default(1);

            // ⚙️ Estado (coherente con cursos/unidades/clases)
            $table->enum('estado', [
                'borrador',              // Recién creado
                'en_revision',           // En revisión
                'oferta_enviada',        // Oferta enviada
                'pendiente_aceptacion',  // Esperando respuesta
                'publicado',             // Visible en plataforma
                'rechazado',             // Rechazado por revisión
                'archivado'              // Antiguo o inactivo
            ])->default('borrador');

            // 🕒 Fechas
            $table->timestamps();
            $table->softDeletes();

            // 🔗 Relaciones
            $table->foreign('idclase')
                ->references('idclase')->on('clases')
                ->cascadeOnDelete();

            // 🧩 Restricción única: no repetir orden dentro de una clase
            $table->unique(['idclase', 'orden']);

            // ⚡ Índice de búsqueda
            $table->index('idclase');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contenidos');
    }
};
