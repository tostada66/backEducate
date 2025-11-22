<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curso_juego', function (Blueprint $table) {
            $table->bigIncrements('idcursojuego');

            // 🔗 Relación con la unidad (ya no con curso)
            $table->unsignedBigInteger('idunidad');
            $table->foreign('idunidad')
                ->references('idunidad')
                ->on('unidades')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // 🔗 Relación con el juego base (catálogo del admin)
            $table->unsignedBigInteger('idjuego');
            $table->foreign('idjuego')
                ->references('idjuego')
                ->on('juegos')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // 🧩 Nombre o temática personalizada (ej: “Memoria de animales”)
            $table->string('nombre_tema', 150)->nullable();

            // 🧠 Nivel o dificultad ajustada para esta unidad
            $table->tinyInteger('nivel')->default(1);

            // 🖼️ Imagen o portada personalizada
            $table->string('imagen', 255)->nullable();

            // ⚙️ Estado del juego
            $table->boolean('activo')->default(true);

            // 📅 Fecha en la que el juego fue dado de baja (si aplica)
            $table->timestamp('fecha_baja')->nullable();

            // 🕓 Fecha programada para eliminación definitiva (tras 1 año)
            $table->timestamp('fecha_eliminacion')->nullable();

            // ⏱️ Auditoría
            $table->timestamps();

            // 🔍 Índices útiles
            $table->index(['idunidad', 'idjuego']);
            $table->index(['activo', 'fecha_baja']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curso_juego');
    }
};
