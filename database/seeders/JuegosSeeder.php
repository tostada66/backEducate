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
                'nombre' => 'Reciclaje',
                'descripcion' => 'Arrastra los residuos al contenedor correcto y aprende a clasificar materiales reciclables de forma divertida.',
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
