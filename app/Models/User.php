<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Ticket;
use App\Models\Ticket as ModelsTicket;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // Konstanta untuk mempermudah pengecekan role di Controller/Blade
    const ROLE_SUPERADMIN = 1;
    const ROLE_ADMIN = 5;
    const ROLE_ATASAN = 3;
    const ROLE_TEKNISI = 4;

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

    public function requester()
    {
        $this->hasMany(ModelsTicket::class, "id", "requester_id");
    }
    public function assignedTo()
    {
        $this->hasMany(ModelsTicket::class, "id", "assigned_to");
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'employee_id',   // FK di users
            'employee_id'    // PK/unique key di employees
        );
    }
}
