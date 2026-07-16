<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'image_path',
        'is_primary'
    ];

    // Frontend selalu memakai field "url" (image.url), bukan "image_path"
    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        if (!$this->image_path) {
            return null;
        }
        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->image_path);
    }

    // Relasi: Gambar ini milik satu produk tertentu
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}