<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoPlanesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = [
            [
                'nombre' => 'Mensual',
                'descripcion' => 'Acceso completo a todos los cursos por 1 mes.',
                'precio' => 10.00,
                'duracion' => 1, // en meses
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nombre' => 'Medio Año',
                'descripcion' => 'Acceso completo durante 6 meses.',
                'precio' => 50.00,
                'duracion' => 6, // en meses
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nombre' => 'Anual',
                'descripcion' => 'Acceso completo a todos los cursos por 12 meses.',
                'precio' => 90.00,
                'duracion' => 12, // en meses
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Inserta o actualiza según el campo único 'nombre'
        DB::table('tipo_planes')->upsert(
            $rows,
            ['nombre'], // clave única
            ['descripcion', 'precio', 'duracion', 'updated_at'] // campos que se actualizan
        );
    }
}
