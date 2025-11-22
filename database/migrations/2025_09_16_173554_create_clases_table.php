<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('clases', function (Blueprint $table) {
            $table->bigIncrements('idclase');
            $table->unsignedBigInteger('idunidad');

            // 📚 Datos de la clase
            $table->string('titulo', 180);
            $table->text('descripcion')->nullable();
            $table->integer('orden')->default(1);

            // ⏱️ Duración total de la clase (sumatoria de sus videos)
            // Guardado en segundos
            $table->unsignedInteger('duracion_total')->default(0);

            // ⚙️ Estado — igual que cursos y unidades
            $table->enum('estado', [
                'borrador',
                'en_revision',
                'oferta_enviada',
                'pendiente_aceptacion',
                'publicado',
                'rechazado',
                'archivado'
            ])->default('borrador');

            // 🕒 Timestamps y SoftDeletes
            $table->timestamps();
            $table->softDeletes();

            // 🔗 Relación
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
