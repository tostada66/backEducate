<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('intento_respuestas', function (Blueprint $table) {
            $table->bigIncrements('idintento_respuesta');

            $table->unsignedBigInteger('idintento');   // intento del estudiante
            $table->unsignedBigInteger('idpregunta');  // pregunta respondida
            $table->unsignedBigInteger('idrespuesta')->nullable(); // opción elegida

            $table->boolean('es_correcta')->default(false);

            $table->timestamps();

            $table->foreign('idintento')
                ->references('idintento')
                ->on('intento_examens')
                ->cascadeOnDelete();

            $table->foreign('idpregunta')
                ->references('idpregunta')
                ->on('preguntas')
                ->cascadeOnDelete();

            $table->foreign('idrespuesta')
                ->references('idrespuesta')
                ->on('respuestas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intento_respuestas');
    }
};
