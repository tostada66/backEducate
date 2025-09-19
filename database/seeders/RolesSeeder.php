<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = [
            ['nombre' => 'estudiante', 'descripcion' => 'Alumno',        'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'profesor',   'descripcion' => 'Docente',       'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'admin',      'descripcion' => 'Administrador', 'created_at' => $now, 'updated_at' => $now],
        ];

        // Si tienes UNIQUE en 'nombre', esto evita duplicados en corridas repetidas
        DB::table('roles')->upsert($rows, ['nombre'], ['descripcion', 'updated_at']);
        // Si no tienes índice único y solo quieres insertar siempre:
        // DB::table('roles')->insert($rows);
    }
}
