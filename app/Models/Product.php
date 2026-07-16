<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'sku',
        'weight',
        'material',
        'dimension',
        'is_active'
    ];

    // Relasi: Setiap produk termasuk ke dalam satu kategori
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    // Relasi ke item pesanan, dipakai untuk sorting "best_selling" & laporan produk terlaris
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}