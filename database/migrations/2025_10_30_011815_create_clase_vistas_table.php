<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clase_vistas', function (Blueprint $table) {
            $table->bigIncrements('idvistaclase');

            $table->unsignedBigInteger('idclase');
            $table->unsignedBigInteger('idcontenido'); // video (único) de la clase
            $table->unsignedBigInteger('idestudiante');
            $table->unsignedBigInteger('idmatricula')->nullable(); // auditoría

            // progreso
            $table->unsignedInteger('ultimo_segundo')->default(0);
            $table->unsignedInteger('segundos_vistos')->default(0);
            $table->unsignedTinyInteger('porcentaje')->default(0); // 0..100
            $table->boolean('completado')->default(false);         // >=60%

            $table->timestamps();

            // FK
            $table->foreign('idclase')->references('idclase')->on('clases')->cascadeOnDelete();
            $table->foreign('idcontenido')->references('idcontenido')->on('contenidos')->cascadeOnDelete();
            $table->foreign('idestudiante')->references('idestudiante')->on('estudiantes')->cascadeOnDelete();
            $table->foreign('idmatricula')->references('idmatricula')->on('matriculas')->nullOnDelete();

            // 1 registro por estudiante y clase (aunque guardemos idcontenido para trazabilidad)
            $table->unique(['idclase', 'idestudiante']);

            $table->index(['idestudiante']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clase_vistas');
    }
};
