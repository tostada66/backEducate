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

            // 🧩 Miniaturas de scrubbing (Plyr)
            $table->string('thumb_vtt', 255)->nullable();
            $table->unsignedSmallInteger('thumb_sprite_w')->nullable();
            $table->unsignedSmallInteger('thumb_sprite_h')->nullable();

            // 📊 Metadatos opcionales (ffprobe)
            $table->unsignedSmallInteger('ancho')->nullable();
            $table->unsignedSmallInteger('alto')->nullable();
            $table->decimal('fps', 6, 3)->nullable();
            $table->unsignedInteger('bitrate_kbps')->nullable();
            $table->string('codec_video', 40)->nullable();
            $table->string('codec_audio', 40)->nullable();
            $table->string('mime_type', 80)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();

            // ⚙️ Estado del procesamiento del video
            $table->enum('estado_proceso', ['pendiente', 'procesando', 'listo', 'fallo'])->default('pendiente');
            $table->timestamp('procesado_en')->nullable();
            $table->text('error_proceso')->nullable();

            // 🗂️ Extra
            $table->string('storage_driver', 40)->nullable();
            $table->string('hash_archivo', 64)->nullable();

            // 🔢 Orden de aparición en la clase
            $table->integer('orden')->default(1);

            // ⚙️ Estado (coherente con cursos/unidades/clases)
            $table->enum('estado', [
                'borrador',
                'en_revision',
                'oferta_enviada',
                'pendiente_aceptacion',
                'publicado',
                'rechazado',
                'archivado'
            ])->default('borrador');

            // 🕒 Fechas + Soft Delete
            $table->timestamps();
            $table->softDeletes();

            // 🔗 Relaciones
            $table->foreign('idclase')
                ->references('idclase')->on('clases')
                ->cascadeOnDelete();

            // 🧩 Unique por clase+orden PERO respetando soft deletes
            $table->unique(['idclase', 'orden', 'deleted_at'], 'contenidos_idclase_orden_deleted_unique');

            // ⚡ Índices útiles
            $table->index(['idclase', 'deleted_at'], 'contenidos_idclase_deleted_idx');
            $table->index(['idclase', 'tipo', 'estado', 'deleted_at'], 'contenidos_listas_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contenidos');
    }
};
