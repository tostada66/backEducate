<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('licencias', function (Blueprint $table) {
            $table->bigIncrements('idlicencia');
            $table->unsignedBigInteger('idcurso');
            $table->unsignedBigInteger('idprofesor'); // dueño/autor
            $table->unsignedBigInteger('idtamano');
            $table->unsignedBigInteger('idtermino');
            $table->decimal('costo', 10, 2);
            $table->date('fechainicio');
            $table->date('fechafin');
            $table->enum('estado', ['solicitada','aprobada','pagada','activa','vencida','rechazada'])->default('solicitada');
            $table->timestamps();

            $table->foreign('idcurso')->references('idcurso')->on('cursos')->cascadeOnDelete();
            $table->foreign('idprofesor')->references('idprofesor')->on('profesores')->restrictOnDelete();
            $table->foreign('idtamano')->references('idtamano')->on('tamano_cursos');
            $table->foreign('idtermino')->references('idtermino')->on('termino_licencias');
        });
    }
    public function down(): void { Schema::dropIfExists('licencias'); }
};