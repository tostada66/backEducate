<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('examenes', function (Blueprint $table) {
            $table->bigIncrements('idexamen');
            $table->unsignedBigInteger('idcurso');
            $table->string('titulo', 180);
            $table->text('descripcion')->nullable();
            $table->unsignedSmallInteger('intentos')->default(1);
            $table->timestamps();

            $table->foreign('idcurso')->references('idcurso')->on('cursos')->cascadeOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('examenes'); }
};
