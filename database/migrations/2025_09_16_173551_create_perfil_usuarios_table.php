<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('perfil_usuarios', function (Blueprint $table) {
            $table->bigIncrements('idperfil');
            $table->unsignedBigInteger('idusuario')->unique();
            $table->string('linkedin_url', 255)->nullable();
            $table->string('github_url', 255)->nullable();
            $table->string('web_url', 255)->nullable();
            $table->text('bio')->nullable();
            $table->timestamps();

            $table->foreign('idusuario')->references('idusuario')->on('usuarios')->cascadeOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('perfil_usuarios'); }
};