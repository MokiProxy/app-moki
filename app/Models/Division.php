<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    // Tambahkan 'code' ke dalam fillable
    protected $fillable = [
        'name', 
        'code', 
        'company_id', 
        'regional_id', 
        'abbreviation'
    ];

    /**
     * Get all of the transaction for the Division
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transaction()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the company that owns the Division
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the regional that owns the Division
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function regional()
    {
        return $this->belongsTo(Regional::class);
    }

    /**
     * Get all of the employees for the Division
     */
   public function employees() {
    return $this->hasMany(Employee::class, 'division_id');
}
}