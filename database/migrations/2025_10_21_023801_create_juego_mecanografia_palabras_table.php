<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('juego_mecanografia_palabras', function (Blueprint $table) {
            $table->bigIncrements('idpalabra');

            // 🔹 Relación con curso_juego (instancia del juego dentro de una unidad)
            $table->unsignedBigInteger('idcursojuego');
            $table->foreign('idcursojuego')
                ->references('idcursojuego')
                ->on('curso_juego')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // 🧠 Palabra o frase que el estudiante debe escribir
            $table->string('palabra', 255);

            // ⏱️ Tiempo límite sugerido (en segundos, por ejemplo)
            $table->integer('tiempo')->default(10);

            // 🎯 Dificultad en texto: fácil, medio o difícil
            $table->enum('dificultad', ['fácil', 'medio', 'difícil'])->default('fácil');

            // ✅ Estado (activa/inactiva)
            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('juego_mecanografia_palabras');
    }
};
