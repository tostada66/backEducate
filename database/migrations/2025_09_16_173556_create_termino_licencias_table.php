<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('termino_licencias', function (Blueprint $table) {
            $table->bigIncrements('idtermino');
            $table->string('nombre', 40)->unique(); // Mensual/Trimestral/Anual
            $table->unsignedSmallInteger('meses')->default(1);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('termino_licencias'); }
};