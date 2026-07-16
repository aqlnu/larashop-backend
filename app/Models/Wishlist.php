<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    protected $fillable = ['user_id', 'product_id'];

    // TAMBAHKAN FUNGSI INI:
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}