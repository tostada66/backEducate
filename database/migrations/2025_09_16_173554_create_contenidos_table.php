<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('contenidos', function (Blueprint $table) {
            $table->bigIncrements('idcontenido');
            $table->unsignedBigInteger('idclase');

            $table->string('titulo', 180);
            $table->text('descripcion')->nullable();

            // Tipo de contenido: texto, video, pdf, link, quiz, etc.
            $table->string('tipo', 50)->default('texto');

            // Enlace o archivo (ruta en storage o URL externa)
            $table->string('url', 255)->nullable();

            // Duración en minutos/segundos (solo aplica si es video)
            $table->unsignedInteger('duracion')->nullable();

            // 👇 cambiado de unsignedInteger() a integer()
            $table->integer('orden')->default(1);

            $table->enum('estado', ['borrador','publicado'])->default('borrador');

            $table->timestamps();
            $table->softDeletes();

            // Relación con clases
            $table->foreign('idclase')
                ->references('idclase')->on('clases')
                ->cascadeOnDelete();

            // Evita orden duplicado dentro de la misma clase
            $table->unique(['idclase','orden']);

            // Índice para optimizar búsquedas por clase
            $table->index('idclase');
        });
    }

    public function down(): void {
        Schema::dropIfExists('contenidos');
    }
};
