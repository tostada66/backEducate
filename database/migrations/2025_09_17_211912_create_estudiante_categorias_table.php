<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('estudiante_categoria', function (Blueprint $table) {
            $table->unsignedBigInteger('idestudiante');
            $table->unsignedBigInteger('idcategoria');

            // Clave primaria compuesta
            $table->primary(['idestudiante','idcategoria']);

            // Relaciones
            $table->foreign('idestudiante')
                  ->references('idestudiante')
                  ->on('estudiantes')
                  ->cascadeOnDelete();

            $table->foreign('idcategoria')
                  ->references('idcategoria')
                  ->on('categorias')
                  ->cascadeOnDelete();

            // Para saber cuándo se creó/actualizó la relación
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiante_categoria');
    }
};
