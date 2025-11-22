<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('juego_reciclaje_items', function (Blueprint $table) {
            $table->bigIncrements('iditem');

            // 🔗 Relación con curso_juego (instancia del juego dentro del curso)
            $table->unsignedBigInteger('idcursojuego');
            $table->foreign('idcursojuego')
                ->references('idcursojuego')
                ->on('curso_juego')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // ♻️ Datos del ítem
            $table->string('nombre'); // Ej: 'Botella de plástico'
            $table->string('tipo');   // Ej: 'plástico', 'papel', 'vidrio', 'orgánico'
            $table->string('imagen')->nullable(); // ruta del ícono o imagen
            $table->boolean('activo')->default(true);

            // 🕒 Auditoría
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('juego_reciclaje_items');
    }
};
