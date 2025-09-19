<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profesores', function (Blueprint $table) {
            $table->bigIncrements('idprofesor');
            $table->unsignedBigInteger('idusuario')->unique();
            $table->text('bio')->nullable();
            $table->string('especialidad', 120)->nullable();
            $table->timestamps();

            $table->foreign('idusuario')->references('idusuario')->on('usuarios')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void { Schema::dropIfExists('profesores'); }
};

