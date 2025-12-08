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
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->bigIncrements('idnotificacion');

            // 🔗 Usuario que recibe la notificación (admin, profe o estudiante)
            $table->unsignedBigInteger('idusuario');

            // Agrupación por módulo / menú
            // ej: 'solicitudes', 'cursos_pendientes', 'pagos', 'mensajes', etc.
            $table->string('categoria', 50);

            // Tipo más específico (opcional)
            // ej: 'curso_enviado', 'curso_aprobado', 'pago_registrado', etc.
            $table->string('tipo', 50)->nullable();

            // Título corto
            $table->string('titulo', 150)->nullable();

            // Mensaje principal
            $table->text('mensaje');

            // Ruta interna del front
            // ej: '/admin/solicitudes', '/profesor/cursos/15'
            $table->string('url', 255)->nullable();

            // Datos adicionales en JSON (ids, nombres, etc.)
            $table->json('datos')->nullable();

            // null = no leída; con fecha = leída
            $table->timestamp('leido_en')->nullable();

            $table->timestamps();

            // 🔐 FK hacia tu tabla usuarios
            $table->foreign('idusuario')
                ->references('idusuario')
                ->on('usuarios')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
