<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('preguntas', function (Blueprint $table) {
            $table->bigIncrements('idpregunta');

            // 🔗 Relación con examen
            $table->unsignedBigInteger('idexamen');

            // 📘 Contenido de la pregunta
            $table->text('texto'); // enunciado principal

            // ⚙️ Configuración de la pregunta
            $table->unsignedSmallInteger('tiempo_segundos')->default(20); // ⏱ tiempo límite
            $table->unsignedTinyInteger('puntos')->default(10);            // 🏆 puntos que otorga

            // 🟩 Control
            $table->boolean('activa')->default(true);

            $table->timestamps();

            // 🔗 Clave foránea
            $table->foreign('idexamen')
                  ->references('idexamen')
                  ->on('examenes')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preguntas');
    }
};
