<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->bigIncrements('idcategoria');
            $table->string('nombre', 120)->unique(); // 👈 ya no hay slug, nombre único
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
