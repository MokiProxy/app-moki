<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
    'category_id', 'supplier_id', 'regional_id', 'brand', 
    'serial_number', 'uid', 'specification', 'production_year', 
    'purchase_date', 'purchase_price', 'condition', 'status',
    'cost_center', 'coa_code', // Pastikan kedua ini ADA
    'location_code', 'sort_number'
];

    /**
     * Relasi ke model Category (Untuk ambil Category Code)
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Relasi ke model Supplier
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Relasi ke model Regional (Untuk ambil Nama Regional)
     */
    public function regional()
    {
        return $this->belongsTo(Regional::class, 'regional_id');
    }

    /**
     * Relasi ke TransactionDetail
     */
    public function transaction_detail()
    {
        return $this->hasMany(TransactionDetail::class, 'asset_id');
    }
}