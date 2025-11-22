<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('curso_categoria', function (Blueprint $table) {
            $table->unsignedBigInteger('idcurso');
            $table->unsignedBigInteger('idcategoria');
            $table->primary(['idcurso','idcategoria']);

            $table->foreign('idcurso')->references('idcurso')->on('cursos')->cascadeOnDelete();
            $table->foreign('idcategoria')->references('idcategoria')->on('categorias')->cascadeOnDelete();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('curso_categoria');
    }
};
