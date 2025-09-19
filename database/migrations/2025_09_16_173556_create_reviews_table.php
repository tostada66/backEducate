<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('reviews', function (Blueprint $table) {
            $table->bigIncrements('idreview');
            $table->unsignedBigInteger('idcurso');
            $table->unsignedBigInteger('idestudiante');
            $table->tinyInteger('rating'); // 1..5
            $table->text('comentario')->nullable();
            $table->timestamps();

            $table->foreign('idcurso')->references('idcurso')->on('cursos')->cascadeOnDelete();
            $table->foreign('idestudiante')->references('idestudiante')->on('estudiantes')->cascadeOnDelete();
            $table->unique(['idcurso','idestudiante']); // una review por estudiante
        });
    }
    public function down(): void { Schema::dropIfExists('reviews'); }
};