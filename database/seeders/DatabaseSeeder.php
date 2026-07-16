<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Memanggil seeder spesifik Anda
        $this->call([
            AdminUserSeeder::class,
            DummyDataSeeder::class, // <-- Tambahkan baris ini
        ]);
    }
}