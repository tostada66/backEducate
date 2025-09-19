<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('clases', function (Blueprint $table) {
            $table->bigIncrements('idclase');
            $table->unsignedBigInteger('idcurso');
            $table->string('titulo', 180);
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('orden')->default(1);
            $table->unsignedInteger('duracion')->nullable(); // minutos
            $table->enum('estado', ['borrador','publicado'])->default('publicado');
            $table->timestamps();
            $table->softDeletes(); // 👈 añade columna deleted_at

            $table->foreign('idcurso')
                  ->references('idcurso')->on('cursos')
                  ->cascadeOnDelete();

            $table->unique(['idcurso','orden']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('clases');
    }
};

