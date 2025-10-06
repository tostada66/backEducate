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
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nombre' => 'Tarjeta',
                'descripcion' => 'Pago con tarjeta de débito o crédito.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('tipos_pagos')->upsert(
            $rows,
            ['nombre'], // clave única
            ['descripcion', 'updated_at']
        );
    }
}
