<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    // Kolom yang diizinkan untuk diisi
    protected $fillable = [
        'name', 
        'slug', 
        'description', 
        'icon'
    ];

    // Relasi: Satu kategori punya banyak produk
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}