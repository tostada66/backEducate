<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Usuario;
use App\Models\Rol;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Aseguramos que exista el rol admin
        $rolAdmin = Rol::firstOrCreate(
            ['nombre' => 'admin'],
            ['descripcion' => 'Administrador del sistema']
        );

        // 2. Creamos el usuario administrador
        Usuario::updateOrCreate(
            ['correo' => 'admin@plataforma.com'], // condición
            [
                'idrol' => $rolAdmin->idrol,
                'nombres' => 'Admin',
                'apellidos' => 'Admin',
                'correo' => 'admin@gmail.com',
                'nombreusuario' => 'admin',
                'telefono' => '77777777',
                'password' => Hash::make('123456789'), // ⚠️ cámbialo luego en producción
                'estado' => 1, // activo
            ]
        );
    }
}
