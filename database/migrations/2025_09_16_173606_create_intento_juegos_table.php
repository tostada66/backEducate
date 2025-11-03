<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('intentos_juego', function (Blueprint $table) {
            $table->bigIncrements('idintento');

            // 🔹 Relación con el estudiante
            $table->unsignedBigInteger('idestudiante');
            $table
                ->foreign('idestudiante')
                ->references('idestudiante')
                ->on('estudiantes')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // 🔹 Relación con el curso_juego (instancia del juego dentro del curso)
            $table->unsignedBigInteger('idcursojuego');
            $table
                ->foreign('idcursojuego')
                ->references('idcursojuego')
                ->on('curso_juego')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // 🔹 Métricas generales de desempeño
            $table->integer('puntaje')->default(0);
            $table->integer('aciertos')->default(0);
            $table->integer('errores')->default(0);
            $table->integer('tiempo')->default(0); // segundos usados
            $table->integer('nivel_superado')->nullable();

            // 🔹 Datos personalizados según el tipo de juego (JSON flexible)
            $table->json('detalles')->nullable();

            // 🔹 Fecha y timestamps
            $table->dateTime('fecha')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intentos_juego');
    }
};
