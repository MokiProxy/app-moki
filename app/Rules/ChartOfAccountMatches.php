<?php

namespace App\Rules;

use App\Models\ChartOfAccount;
use Illuminate\Contracts\Validation\Rule;

/**
 * Jaga agar COA yang dipilih benar-benar hasil komposisi Pusat Biaya (dan
 * bila relevan, Elemen Biaya) yang sama dengan yang dipilih pengguna.
 *
 * Tanpa rule ini, form transaksi bisa menyimpan COA milik Pusat Biaya lain —
 * termasuk fallback `CostElement::coaSuggestion()` yang mengambil satu COA
 * sembarang. Akibatnya kode a..d pada baris transaksi tidak lagi cocok dengan
 * Pusat Biaya yang direferensikan.
 */
class ChartOfAccountMatches implements Rule
{
    /**
     * @param  string  $costCenterField  nama field Pusat Biaya pada request yang sama
     * @param  string|null  $costElementField  nama field Elemen Biaya, null bila form tidak punya
     * @param  string|null  $type  tipe COA yang diizinkan (mis. `expense`), null bila bebas
     */
    public function __construct(
        private readonly string $costCenterField = 'cost_center_id',
        private readonly ?string $costElementField = null,
        private readonly ?string $type = null,
    ) {}

    public function passes($attribute, $value): bool
    {
        if (! filled($value)) {
            // Field opsional yang dikosongkan tidak bisa bertentangan; pasangan
            // kosong ini ditangani pemanggil (atau dibiarkan fallback).
            return true;
        }

        $account = ChartOfAccount::query()->find($value);

        if (! $account) {
            // Keberadaan ID-nya sudah dicek rule `exists`.
            return true;
        }

        if ($this->type !== null && $account->type !== $this->type) {
            return false;
        }

        $costCenterId = request()->input($this->costCenterField);

        if (! filled($costCenterId)) {
            // COA selalu hasil komposisi Pusat Biaya + Elemen Biaya, jadi COA
            // yang dipilih tanpa Pusat Biaya akan menggantung di udara. Form
            // mewajibkan Pusat Biaya, tapi payload crafted harus ditolak juga.
            return false;
        }

        if ((int) $account->cost_center_id !== (int) $costCenterId) {
            return false;
        }

        if ($this->costElementField !== null) {
            $costElementId = request()->input($this->costElementField);

            if (filled($costElementId) && (int) $account->cost_element_id !== (int) $costElementId) {
                return false;
            }
        }

        return true;
    }

    public function message(): string
    {
        $target = $this->costElementField !== null
            ? 'Pusat Biaya dan Elemen Biaya yang dipilih'
            : 'Pusat Biaya yang dipilih';

        return "Chart of Account tidak sesuai dengan {$target}. COA harus hasil komposisi keduanya, bukan COA milik Pusat Biaya lain.";
    }
}
