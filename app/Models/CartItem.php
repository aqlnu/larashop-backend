<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = ['cart_id', 'product_id', 'quantity'];

    // Relasi balik ke keranjang
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    // Relasi ke produk (untuk mengambil harga, nama barang, dll)
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}