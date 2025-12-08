<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('curso_ediciones', function (Blueprint $table) {
            $table->id();

            // 🔗 Curso y profesor (según tu esquema actual)
            $table->unsignedBigInteger('idcurso');
            $table->unsignedBigInteger('idprofesor');

            // Motivo que escribe el profesor al pedir edición
            $table->string('motivo', 500)->nullable();

            // Estados de la solicitud de edición
            $table->enum('estado', [
                'pendiente',   // Profe pidió edición, esperando respuesta del admin
                'en_edicion',  // Admin aprobó, profe puede editar el curso
                'en_revision', // Profe terminó cambios y los mandó a revisión
                'rechazada',   // Admin rechazó esta solicitud
                'cerrada',     // Admin cerró el ciclo (curso queda publicado y bloqueado)
            ])->default('pendiente');

            // Fechas útiles
            $table->timestamp('aprobado_at')->nullable();
            $table->timestamp('cerrado_at')->nullable();

            $table->timestamps();

            // 🔐 Claves foráneas
            $table->foreign('idcurso')
                ->references('idcurso')->on('cursos')
                ->onDelete('cascade');

            $table->foreign('idprofesor')
                ->references('idprofesor')->on('profesores')
                ->onDelete('cascade');

            // Opcional: índices para consultas rápidas
            $table->index(['idcurso', 'estado']);
            $table->index(['idprofesor', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curso_ediciones');
    }
};
