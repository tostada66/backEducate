<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('contenidos', function (Blueprint $table) {
            $table->bigIncrements('idcontenido');
            $table->unsignedBigInteger('idclase');
            $table->string('titulo', 180);
            $table->text('descripcion')->nullable();
            $table->string('tipo', 50)->default('texto'); // texto, video, pdf, link
            $table->string('url', 255)->nullable(); // enlace archivo/video/pdf
            $table->unsignedInteger('orden')->default(1);
            $table->enum('estado', ['borrador','publicado'])->default('borrador');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('idclase')->references('idclase')->on('clases')->cascadeOnDelete();
            $table->unique(['idclase','orden']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('contenidos');
    }
};
