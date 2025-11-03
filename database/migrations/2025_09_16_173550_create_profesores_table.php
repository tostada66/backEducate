<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('profesores', function (Blueprint $table) {
            $table->bigIncrements('idprofesor');
            $table->unsignedBigInteger('idusuario')->unique();

            // Info básica
            $table->text('bio')->nullable();
            $table->string('especialidad', 120)->nullable();

            // ✅ Campos adicionales
            $table->string('direccion', 150)->nullable();
            $table->string('pais', 100)->nullable();
            $table->string('empresa', 150)->nullable();
            $table->string('cargo', 120)->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->text('detalles')->nullable();

            // 👇 Nuevo campo para aprobar/rechazar
            $table->enum('estado_aprobacion', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');

            $table->timestamps();

            // FK
            $table->foreign('idusuario')
                  ->references('idusuario')
                  ->on('usuarios')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesores');
    }
};
