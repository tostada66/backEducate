<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('categorias')->insert([
            [
                'nombre' => '1° de Primaria',
                'descripcion' => 'Conteo y comparación de cantidades, números hasta 100, sumas y restas simples con apoyo de dibujos y objetos.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => '2° de Primaria',
                'descripcion' => 'Números hasta 1.000, sumas y restas con llevadas, introducción a la multiplicación y resolución de problemas sencillos.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => '3° de Primaria',
                'descripcion' => 'Tablas de multiplicar, división básica, problemas con varias operaciones y nociones iniciales de fracciones y figuras geométricas.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => '4° de Primaria',
                'descripcion' => 'Operaciones con números más grandes, multiplicación y división más complejas, fracciones sencillas, perímetro y área básica.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => '5° de Primaria',
                'descripcion' => 'Operaciones con fracciones y decimales, porcentajes iniciales, proporcionalidad simple y problemas de geometría y medición.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => '6° de Primaria',
                'descripcion' => 'Operaciones combinadas, introducción al álgebra básica, porcentajes, razones, estadística y problemas de la vida cotidiana más complejos.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
