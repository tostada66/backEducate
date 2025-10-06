<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Usuario;
use App\Models\Estudiante;
use App\Models\Rol;

class EstudianteSeeder extends Seeder
{
    public function run(): void
    {
        $rolEstudiante = Rol::firstOrCreate(
            ['nombre' => 'estudiante'],
            ['descripcion' => 'Usuario inscrito en la plataforma para tomar cursos']
        );

        $usuario = Usuario::firstOrCreate(
            ['correo' => 'estu@gmail.com'],
            [
                'idrol'          => $rolEstudiante->idrol,
                'nombres'        => 'María Fernanda',
                'apellidos'      => 'Pérez Vargas',
                'nombreusuario'  => 'estu',
                'telefono'       => '76543210',
                'password'       => Hash::make('123456789'),
                'estado'         => 1,
            ]
        );

        Estudiante::firstOrCreate(
            ['idusuario' => $usuario->idusuario],
            ['nivelacademico' => 'Intermedio']
        );

        echo "✅ Estudiante de prueba creado (correo: estu@gmail.com | contraseña: 123456789)\n";
    }
}
