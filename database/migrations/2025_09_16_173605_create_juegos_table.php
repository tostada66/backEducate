<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('juegos', function (Blueprint $table) {
            $table->bigIncrements('idjuego');

            // 🔹 Nombre del juego base (ej: Mecanografía, Memoria, etc.)
            $table->string('nombre', 150)->unique();

            // 🔹 Descripción general o instrucciones
            $table->text('descripcion')->nullable();

            // 🔹 Estado del juego (activo/inactivo)
            $table->boolean('activo')->default(true);

            // 🔹 Auditoría
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('juegos');
    }
};
