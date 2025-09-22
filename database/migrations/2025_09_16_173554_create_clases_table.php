<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clases', function (Blueprint $table) {
            $table->id('idclase');
            $table->unsignedBigInteger('idunidad');

            $table->string('titulo');
            $table->text('descripcion')->nullable();

            // 👇 antes estaba ->unsigned(), ya lo quitamos
            $table->integer('orden')->default(1);

            $table->enum('estado', ['borrador', 'publicado'])->default('borrador');

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('idunidad')
                  ->references('idunidad')
                  ->on('unidades')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clases');
    }
};
