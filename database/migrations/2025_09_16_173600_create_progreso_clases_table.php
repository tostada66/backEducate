<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('progreso_clases', function (Blueprint $table) {
            $table->bigIncrements('idprogreso');
            $table->unsignedBigInteger('idmatricula');
            $table->unsignedBigInteger('idclase');
            $table->boolean('completado')->default(false);
            $table->unsignedTinyInteger('progreso')->default(0); // 0..100
            $table->timestamp('ultima_vista_at')->nullable();
            $table->timestamps();

            $table->foreign('idmatricula')->references('idmatricula')->on('matriculas')->cascadeOnDelete();
            $table->foreign('idclase')->references('idclase')->on('clases')->cascadeOnDelete();
            $table->unique(['idmatricula','idclase']);
        });
    }
    public function down(): void { Schema::dropIfExists('progreso_clases'); }
};
