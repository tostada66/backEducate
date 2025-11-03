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

            // ⚙️ Estado — igual que cursos y unidades
            $table->enum('estado', [
                'borrador',              // Recién creada
                'en_revision',           // En revisión por el admin
                'oferta_enviada',        // Admin envió oferta (solo reflejo del curso)
                'pendiente_aceptacion',  // Esperando respuesta del profesor
                'publicado',             // Activa y visible
                'rechazado',             // Rechazada por admin
                'archivado'              // Desactivada o antigua
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
