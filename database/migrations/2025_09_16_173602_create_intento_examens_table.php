<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('intento_examens', function (Blueprint $table) {
            $table->bigIncrements('idintento');

            // 🔗 Relaciones principales
            $table->unsignedBigInteger('idexamen');
            $table->unsignedBigInteger('idestudiante');

            // ⚙️ Estado del intento
            $table->unsignedTinyInteger('vidas_restantes')->default(3); // ❤️ vidas al finalizar
            $table->unsignedTinyInteger('puntaje')->default(0);          // 📊 % obtenido
            $table->boolean('aprobado')->default(false);                 // ✅ pasó o no

            // ⏱️ Tiempos de control
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_fin')->nullable();

            // 🔁 Permite ver si el intento fue completado
            $table->enum('estado', ['en_progreso', 'completado', 'abandonado'])->default('en_progreso');

            $table->timestamps();

            // 🔗 Claves foráneas
            $table->foreign('idexamen')
                  ->references('idexamen')
                  ->on('examenes')
                  ->cascadeOnDelete();

            $table->foreign('idestudiante')
                  ->references('idestudiante')
                  ->on('estudiantes')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intento_examens');
    }
};
