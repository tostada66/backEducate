<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Juego;

class JuegosSeeder extends Seeder
{
    public function run(): void
    {
        $juegosBase = [
            [
                'nombre' => 'Mecanografía',
                'descripcion' => 'Practica tu velocidad de escritura y precisión al escribir palabras correctamente.',
                'activo' => true,
            ],
            [
                'nombre' => 'Cartas de Memoria',
                'descripcion' => 'Encuentra las parejas de cartas iguales lo más rápido posible para mejorar tu memoria visual.',
                'activo' => true,
            ],
            [
                'nombre' => 'Clasifica Operaciones',
                'descripcion' => 'Arrastra cada ejercicio al tipo de operación correcta (suma, resta, multiplicación, división, fracciones, potencias, etc.) y refuerza tus habilidades matemáticas de forma divertida.',
                'activo' => true,
            ],
        ];

        foreach ($juegosBase as $data) {
            Juego::updateOrCreate(
                ['nombre' => $data['nombre']], // 🔹 criterio único
                $data
            );
        }
    }
}
