<?php

namespace Database\Seeders;

use App\Models\Profesor;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProfesorSeeder extends Seeder
{
    public function run(): void
    {
        $rolProfesor = Rol::firstOrCreate(
            ['nombre' => 'profesor'],
            ['descripcion' => 'Usuario encargado de crear y dictar cursos']
        );

        $usuario = Usuario::firstOrCreate(
            ['correo' => 'profe@gmail.com'],
            [
                'idrol'          => $rolProfesor->idrol,
                'nombres'        => 'Carlos Andrés',
                'apellidos'      => 'Gutiérrez Morales',
                'nombreusuario'  => 'profe',
                'telefono'       => '71234567',
                'password'       => Hash::make('123456789'),
                'estado'         => 1,
            ]
        );

        Profesor::firstOrCreate(
            ['idusuario' => $usuario->idusuario],
            [
                'bio'                => 'Ingeniero de sistemas con 10 años de experiencia en desarrollo web y enseñanza universitaria.',
                'especialidad'       => 'Desarrollo Full Stack',
                'direccion'          => 'Av. América #123',
                'pais'               => 'Bolivia',
                'empresa'            => 'CodeLab Academy',
                'cargo'              => 'Instructor Principal',
                'fecha_inicio'       => '2015-03-01',
                'fecha_fin'          => null,
                'detalles'           => 'Apasionado por la tecnología y la educación digital.',
                'estado_aprobacion'  => 'aprobado',
            ]
        );

        echo "✅ Profesor de prueba creado (correo: profe@gmail.com | contraseña: 123456789)\n";
    }
}
