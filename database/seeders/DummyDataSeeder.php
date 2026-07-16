<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $catLiving = Category::create([
            'name' => 'Living Room',
            'slug' => 'living-room',
            'description' => 'Perabotan ruang tamu'
        ]);

        $catBed = Category::create([
            'name' => 'Bedroom',
            'slug' => 'bedroom',
            'description' => 'Perabotan kamar tidur'
        ]);

        // Baris 'is_new' sudah dihapus dari sini
        Product::create([
            'category_id' => $catLiving->id,
            'name' => 'Sofa Mewah Estetik',
            'slug' => 'sofa-mewah-estetik',
            'price' => 3500000,
            'description' => 'Sofa empuk anti pegal.',
            'stock' => 15,
        ]);

        // Baris 'is_new' sudah dihapus dari sini
        Product::create([
            'category_id' => $catBed->id,
            'name' => 'Kasur Springbed King Size',
            'slug' => 'kasur-springbed-king-size',
            'price' => 5000000,
            'description' => 'Kasur nyaman membuat tidur nyenyak.',
            'stock' => 5,
        ]);
    }
}