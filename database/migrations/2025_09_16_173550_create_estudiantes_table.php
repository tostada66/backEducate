<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->bigIncrements('idestudiante');
            $table->unsignedBigInteger('idusuario')->unique();
            $table->string('nivelacademico', 80)->nullable();
            $table->timestamps();

            $table->foreign('idusuario')->references('idusuario')->on('usuarios')->cascadeOnDelete();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
