<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories'; // Pastikan nama tabel benar
    protected $guarded = [];

    // --- TAMBAHKAN FUNGSI INI ---
    public function assets()
    {
        // Parameter kedua adalah foreign key di tabel assets (category_id)
        return $this->hasMany(Asset::class, 'category_id', 'id');
    }
}