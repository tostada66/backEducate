<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategoriaSeeder::class,
            RolesSeeder::class,
            TipoPlanesSeeder::class, // 💳 tipos de planes
            TipoPagoSeeder::class,   // 💰 tipos de pago
            AdminSeeder::class,      // 👑 usuario administrador
            ProfesorSeeder::class,   // 👨‍🏫 profesor de prueba
            EstudianteSeeder::class, // 🎓 estudiante de prueba
        ]);
    }
}
