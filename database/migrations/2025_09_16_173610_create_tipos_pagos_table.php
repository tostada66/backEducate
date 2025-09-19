<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_pagos', function (Blueprint $table) {
            $table->bigIncrements('idpago');     // PK del catálogo (si prefieres, cámbiale el nombre a idtipo_pago)
            $table->string('nombre', 80);        // Efectivo, Tarjeta, QR, etc.
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_pagos');
    }
};
