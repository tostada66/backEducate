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
                'nombre' => 'Programación',
                'descripcion' => 'JavaScript, Python, Java y más',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Diseño',
                'descripcion' => 'UX/UI, gráfico, web',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Data Science',
                'descripcion' => 'Análisis de datos y ML',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Lenguajes',
                'descripcion' => 'Idiomas como inglés, francés, alemán',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Ciberseguridad',
                'descripcion' => 'Análisis de sistemas y vulnerabilidades',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Lógica',
                'descripcion' => 'Matemáticas, algoritmos y pensamiento crítico',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
