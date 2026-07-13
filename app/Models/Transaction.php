<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = ['order_number', 'employee_id', 'division_id', 'note', 'status', 'type'];

    public function employee() {
        return $this->belongsTo(Employee::class);
    }

    public function division() {
        return $this->belongsTo(Division::class);
    }

    // RELASI INI WAJIB ADA
   public function details()
{
    // Pastikan nama model dan foreign key-nya sesuai
    return $this->hasMany(TransactionDetail::class, 'transaction_id');
}
}