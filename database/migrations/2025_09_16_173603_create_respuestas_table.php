<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('respuestas', function (Blueprint $table) {
            $table->bigIncrements('idrespuesta');

            // 🔗 Relación con la pregunta
            $table->unsignedBigInteger('idpregunta');

            // 📘 Contenido de la respuesta
            $table->text('texto');

            // ✅ Indica si esta opción es la correcta
            $table->boolean('es_correcta')->default(false);

            // 🟢 Permite ocultar opciones (si se desactiva temporalmente)
            $table->boolean('activa')->default(true);

            $table->timestamps();

            // 🔗 Clave foránea
            $table->foreign('idpregunta')
                  ->references('idpregunta')
                  ->on('preguntas')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respuestas');
    }
};
