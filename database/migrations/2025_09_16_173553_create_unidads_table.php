<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('unidades', function (Blueprint $table) {
            $table->bigIncrements('idunidad');
            $table->unsignedBigInteger('idcurso'); // FK hacia cursos

            // 📚 Datos de la unidad
            $table->string('titulo', 180);
            $table->text('descripcion')->nullable();
            $table->text('objetivos')->nullable();

            // 🖼 Imagen (opcional, como en cursos)
            $table->string('imagen', 255)->nullable();

            // ⏱ Duración estimada (opcional, suma de clases de la unidad)
            $table->integer('duracion_estimada')->nullable();

            // ⚙️ Estado
            $table->enum('estado', ['borrador','publicado'])->default('borrador');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Relación con cursos
            $table->foreign('idcurso')
                  ->references('idcurso')->on('cursos')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('unidades');
    }
};
