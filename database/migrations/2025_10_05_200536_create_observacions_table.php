<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('observaciones', function (Blueprint $table) {
            $table->bigIncrements('idobservacion');

            // 🔗 Relaciones opcionales
            $table->unsignedBigInteger('idcurso')->nullable();
            $table->unsignedBigInteger('idoferta')->nullable();
            $table->unsignedBigInteger('idusuario')->nullable();

            // 💬 Datos principales
            $table->enum('tipo', [
                'rechazo',       // motivo o sugerencia del rechazo (curso u oferta)
                'contraoferta',  // negociación de oferta
                'sistema',       // acciones automáticas del sistema
            ])->default('rechazo');

            $table->text('comentario');
            $table->timestamps();

            // 🔗 Llaves foráneas (opcionales)
            $table->foreign('idcurso')
                ->references('idcurso')->on('cursos')
                ->cascadeOnDelete();

            $table->foreign('idoferta')
                ->references('idoferta')->on('ofertas')
                ->cascadeOnDelete();

            $table->foreign('idusuario')
                ->references('idusuario')->on('usuarios') // ✅ corregido aquí
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observaciones');
    }
};
