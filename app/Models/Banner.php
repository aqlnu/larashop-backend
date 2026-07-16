<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'link',
        'image_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Frontend memakai field "image" langsung sebagai URL (b.image)
    protected $appends = ['image'];

    public function getImageAttribute()
    {
        if (!$this->image_path) {
            return null;
        }
        return Storage::disk('public')->url($this->image_path);
    }
}
