<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // Konstanta untuk mempermudah pengecekan role di Controller/Blade
    const ROLE_SUPERADMIN = 1;
    const ROLE_ADMIN = 2;
    const ROLE_ATASAN = 3;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_id', // Tambahkan ini agar bisa disimpan via Controller
        'role_id',     // Tambahkan ini agar bisa disimpan via Controller
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Helper untuk cek role di Blade atau Controller
     * Contoh penggunaan: if(Auth::user()->isSuperAdmin())
     */
    public function isSuperAdmin()
    {
        return $this->role_id === self::ROLE_SUPERADMIN;
    }

    public function isAdmin()
    {
        return $this->role_id === self::ROLE_ADMIN;
    }

    public function isAtasan()
    {
        return $this->role_id === self::ROLE_ATASAN;
    }
}