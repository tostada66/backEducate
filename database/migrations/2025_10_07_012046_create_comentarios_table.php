<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comentarios', function (Blueprint $table) {
            $table->bigIncrements('idcomentario'); // identificador principal

            // 🔗 Relaciones
            $table->unsignedBigInteger('idclase');     // Clase a la que pertenece
            $table->unsignedBigInteger('idusuario');   // Usuario que comenta
            $table->unsignedBigInteger('idpadre')->nullable(); // Si es respuesta a otro comentario

            // 💬 Contenido
            $table->text('contenido');

            // 🕒 Tiempos
            $table->timestamps();

            // 🔗 Llaves foráneas
            $table->foreign('idclase')
                ->references('idclase')
                ->on('clases')
                ->cascadeOnDelete();

            $table->foreign('idusuario')
                ->references('idusuario')
                ->on('usuarios')
                ->cascadeOnDelete();

            $table->foreign('idpadre')
                ->references('idcomentario')
                ->on('comentarios')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comentarios');
    }
};
