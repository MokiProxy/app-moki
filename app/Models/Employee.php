<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    protected $fillable = [
        'employee_id',
        'name',
        'jabatan',
        'division_id',
        'regional_id',
        'hp',
        'email',
        'address',
        'golongan_darah_id',
        'jenis_kelamin_id',
    ];

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function regional()
    {
        return $this->belongsTo(Regional::class, 'regional_id');
    }

    public function golonganDarah()
    {
        return $this->belongsTo(GolonganDarah::class, 'golongan_darah_id');
    }

    public function jenisKelamin()
    {
        return $this->belongsTo(JenisKelamin::class, 'jenis_kelamin_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'employee_id', 'employee_id');
    }

    public function hirarki(): HasOne
    {
        return $this->hasOne(
            PegawaiHirarki::class,
            'employee_id',
            'employee_id'
        );
    }

    public function superior1(): ?self
    {
        $hirarki = PegawaiHirarki::where('employee_id', $this->employee_id)->first();

        if (! $hirarki || ! $hirarki->employee_id_hier1) {
            return null;
        }

        return static::where('employee_id', $hirarki->employee_id_hier1)->first();
    }

    public function superior(int $level): ?self
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

    public function findSuperiorByJabatan(string $jabatan): ?self
    {
        $hirarki = PegawaiHirarki::where('employee_id', $this->employee_id)->first();

        if (! $hirarki) {
            return null;
        }

        for ($i = 1; $i <= 8; $i++) {
            $jabatanField = $hirarki->{"jabatan{$i}"};

            if ($jabatanField === $jabatan) {
                $employeeId = $hirarki->{"employee_id_hier{$i}"};

                if ($employeeId) {
                    return static::where('employee_id', $employeeId)->first();
                }
            }
        }

        return null;
    }
}
