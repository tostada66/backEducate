<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('tipo_planes', function (Blueprint $table) {
            $table->bigIncrements('idplan');
            $table->string('nombre', 100)->unique();
            $table->string('descripcion', 255)->nullable();
            $table->decimal('precio', 10, 2);
            $table->unsignedInteger('duracion'); // 🔹 duración en meses
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_planes');
    }
};
