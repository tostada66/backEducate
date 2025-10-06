<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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

            // ⚙️ Estado del curso y timestamps
            $table->enum('estado', [
                'borrador',              // Curso recién creado
                'en_revision',           // Enviado al admin para revisión
                'oferta_enviada',        // Admin envió una oferta
                'pendiente_aceptacion',  // Profesor debe aceptar/rechazar oferta
                'publicado',             // Curso activo en la plataforma
                'rechazado',             // Curso rechazado por el admin o profesor
                'archivado'              // Curso antiguo o retirado
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
