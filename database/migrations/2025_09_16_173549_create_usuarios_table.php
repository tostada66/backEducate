<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {Schema::create('usuarios', function (Blueprint $table) {
        $table->bigIncrements('idusuario');
        $table->unsignedBigInteger('idrol')->nullable();
        $table->string('nombres', 100);
        $table->string('apellidos', 100);
        $table->string('correo', 191)->unique();
        $table->string('nombreusuario', 60)->unique();
        // 👇 nuevo campo teléfono
        $table->string('telefono', 30)->nullable()->index();
        $table->string('password', 191)->nullable();
        $table->tinyInteger('estado')->default(1);
        $table->string('foto', 255)->nullable();
        $table->timestamp('email_verified_at')->nullable();
        $table->rememberToken();
        $table->timestamps();

        $table->foreign('idrol')->references('idrol')->on('roles')->nullOnDelete();
    });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('usuarios');
    }
};
