<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;   // 👈 IMPORTANTE

class CatalogosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // tipo_planes (asegúrate que la tabla exista antes de sembrar)
        DB::table('tipo_planes')->insertOrIgnore([
            ['nombre' => 'Básico', 'descripcion' => 'Acceso estándar', 'precio' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // tipos_pagos
        DB::table('tipos_pagos')->insertOrIgnore([
            ['nombre' => 'Efectivo', 'descripcion' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
