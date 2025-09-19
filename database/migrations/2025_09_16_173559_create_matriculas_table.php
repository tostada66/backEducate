<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('matriculas', function (Blueprint $table) {
            $table->bigIncrements('idmatricula');
            $table->unsignedBigInteger('idestudiante');
            $table->unsignedBigInteger('idcurso');
            $table->date('fecha')->nullable();
            $table->enum('estado', ['activa','completada','cancelada'])->default('activa');
            $table->timestamps();

            $table->foreign('idestudiante')->references('idestudiante')->on('estudiantes')->cascadeOnDelete();
            $table->foreign('idcurso')->references('idcurso')->on('cursos')->cascadeOnDelete();
            $table->unique(['idestudiante','idcurso']);
        });
    }
    public function down(): void { Schema::dropIfExists('matriculas'); }
};