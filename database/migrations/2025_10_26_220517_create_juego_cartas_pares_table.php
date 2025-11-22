<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('juego_cartas_pares', function (Blueprint $table) {
            $table->bigIncrements('idpar');

            // 🔗 Relación con curso_juego (instancia del juego dentro del curso)
            $table->unsignedBigInteger('idcursojuego');
            $table->foreign('idcursojuego')
                ->references('idcursojuego')
                ->on('curso_juego')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // 🧩 Carta A
            $table->string('texto_a', 150)->nullable();
            $table->string('imagen_a', 255)->nullable();

            // 🧩 Carta B
            $table->string('texto_b', 150)->nullable();
            $table->string('imagen_b', 255)->nullable();

            // ⚙️ Estado de la carta (por si deseas ocultarla temporalmente)
            $table->boolean('activo')->default(true);

            // 🕒 Auditoría
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('juego_cartas_pares');
    }
};
