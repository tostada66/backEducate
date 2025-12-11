<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoPagoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = [
            [
                'nombre' => 'QR',
                'descripcion' => 'Pago mediante código QR escaneado desde la app bancaria.',
            ],
            [
                'nombre' => 'Tarjeta',
                'descripcion' => 'Pago con tarjeta de débito o crédito.',
            ],
        ];

        foreach ($rows as $row) {
            DB::table('tipos_pagos')->updateOrInsert(
                ['nombre' => $row['nombre']], // criterio de búsqueda
                [
                    'descripcion' => $row['descripcion'],
                    'updated_at'  => $now,
                    'created_at'  => $now,
                ]
            );
        }
    }
}
