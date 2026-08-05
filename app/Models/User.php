<?php

namespace App\Models;

use App\Models\Ticket as ModelsTicket;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_id',
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

    public function requester()
    {
        $this->hasMany(ModelsTicket::class, 'id', 'requester_id');
    }

    public function assignedTo()
    {
        $this->hasMany(ModelsTicket::class, 'id', 'assigned_to');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'employee_id',   // FK di users
            'employee_id'    // PK/unique key di employees
        );
    }

    public function pegawaiHirarki(): HasOne
    {
        return $this->hasOne(
            PegawaiHirarki::class,
            'employee_id',
            'employee_id'
        );
    }

    public function superior1(): ?User
    {
        $hirarki = PegawaiHirarki::where('employee_id', $this->employee_id)->first();

        if (! $hirarki || ! $hirarki->employee_id_hier1) {
            return null;
        }

        return static::where('employee_id', $hirarki->employee_id_hier1)->first();
    }

    public function superior(int $level): ?User
    {
        if ($level < 1 || $level > 8) {
            return null;
        }

        $hirarki = PegawaiHirarki::where('employee_id', $this->employee_id)->first();

        if (! $hirarki) {
            return null;
        }

        $employeeId = $hirarki->{"employee_id_hier{$level}"};

        if (! $employeeId) {
            return null;
        }

        return static::where('employee_id', $employeeId)->first();
    }

    public function superiorChain(int $maxLevel = 8): Collection
    {
        $hirarki = PegawaiHirarki::where('employee_id', $this->employee_id)->first();

        if (! $hirarki) {
            return new Collection;
        }

        $superiors = collect();

        for ($i = 1; $i <= $maxLevel; $i++) {
            $employeeId = $hirarki->{"employee_id_hier{$i}"};

            if (! $employeeId) {
                break;
            }

            $superior = static::where('employee_id', $employeeId)->first();

            if ($superior) {
                $superiors->put($i, $superior);
            }
        }

        return $superiors;
    }
}
