<?php

namespace App\Rules;

use App\Models\ChartOfAccount;
use Illuminate\Contracts\Validation\Rule;

/**
 * Jaga agar COA yang dipilih berada di dalam divisi yang dipilih pengguna.
 *
 * `chart_of_accounts` tidak menyimpan `division_id`; divisi suatu COA berasal
 * dari Pusat Biaya-nya. Form Rencana Pendapatan & Beban tidak punya kolom
 * `cost_center_id`, jadi divisi adalah satu-satunya konteks organisasi yang
 * menghubungkan baris transaksi dengan COA.
 */
class ChartOfAccountBelongsToDivision implements Rule
{
    public function __construct(private readonly string $divisionField = 'division_id') {}

    public function passes($attribute, $value): bool
    {
        if (! filled($value)) {
            // Field opsional yang dikosongkan tidak bisa bertentangan.
            return true;
        }

        $divisionId = request()->input($this->divisionField);

        if (! filled($divisionId)) {
            return true;
        }

        return ChartOfAccount::query()
            ->whereKey($value)
            ->whereHas('costCenter', fn ($query) => $query->where('division_id', $divisionId))
            ->exists();
    }

    public function message(): string
    {
        return 'Chart of Account tidak tersedia pada divisi yang dipilih. COA harus milik Pusat Biaya divisi tersebut.';
    }
}
