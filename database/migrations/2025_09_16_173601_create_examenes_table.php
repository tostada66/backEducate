<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('examenes', function (Blueprint $table) {
            $table->bigIncrements('idexamen');

            // 🔗 Relación con unidad
            $table->unsignedBigInteger('idunidad');

            // 📘 Información general
            $table->string('titulo', 180);
            $table->text('descripcion')->nullable();

            // ⚙️ Configuración del examen
            $table->unsignedInteger('duracion_segundos')->default(0);       // ⏱ duración total calculada
            $table->unsignedTinyInteger('vidas')->default(3);               // ❤️ vidas por intento
            $table->unsignedTinyInteger('minimo_aprobacion')->default(70);  // ✅ % mínimo para aprobar
            $table->boolean('activo')->default(true);                       // estado del examen

            $table->timestamps();

            // 🔗 Clave foránea
            $table->foreign('idunidad')
                  ->references('idunidad')
                  ->on('unidades')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examenes');
    }
};
