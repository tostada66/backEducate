<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cursos', function (Blueprint $table) {
            $table->bigIncrements('idcurso');
            $table->unsignedBigInteger('idprofesor');
            $table->string('nombre', 150);
            $table->string('slug', 180)->unique();
            $table->text('descripcion')->nullable();
            $table->string('nivel', 30)->nullable(); // Básico/Intermedio/Avanzado
            $table->string('imagen', 255)->nullable();
            $table->integer('duracion_estimada')->nullable(); // en minutos
            $table->integer('numero_clases')->default(0);
            $table->enum('estado', ['borrador','publicado','archivado'])->default('borrador');
            $table->date('fecha_creacion')->nullable();
            $table->timestamps();

            // 👇 Campo SoftDeletes (deleted_at)
            $table->softDeletes();

            $table->foreign('idprofesor')
                ->references('idprofesor')
                ->on('profesores')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('cursos');
    }
};
