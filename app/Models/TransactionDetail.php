<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
    'transaction_id',
    'asset_id',
    'new_uid', // Tambahkan ini jika belum ada
];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the asset that owns the TransactionDetail
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
