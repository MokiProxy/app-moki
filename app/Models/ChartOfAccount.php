<?php

namespace App\Models;

use App\Models\Erkap\CostCenter;
use App\Models\Erkap\CostElement;
use App\Support\CoaCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'cost_center_id', 'cost_element_id', 'name', 'type', 'description'];

    protected static function booted()
    {
        static::saving(function (self $chartOfAccount) {
            $chartOfAccount->syncComposedCode();
        });
    }

    /* ---------------------------------------------------------------------
     | Relasi
     | ------------------------------------------------------------------ */

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function costElement(): BelongsTo
    {
        return $this->belongsTo(CostElement::class, 'cost_element_id');
    }

    public function costElements(): HasMany
    {
        return $this->hasMany(CostElement::class, 'chart_of_account_id');
    }

    /* ---------------------------------------------------------------------
     | Scope
     | ------------------------------------------------------------------ */

    public function scopeRevenue($query)
    {
        return $query->where('type', 'revenue');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    /**
     * Satu pasangan Pusat Biaya + Elemen Biaya menghasilkan tepat satu COA,
     * dijamin oleh unique index `chart_of_accounts_composition_unique`.
     *
     * Inilah alasan form transaksi boleh menurunkan COA secara deterministik
     * alih-alih meminta pengguna memilih dari daftar yang isinya maksimal satu
     * opsi sah.
     */
    public function scopeForPair($query, ?int $costCenterId, ?int $costElementId)
    {
        return $query
            ->where('cost_center_id', $costCenterId)
            ->where('cost_element_id', $costElementId);
    }

    public static function idForPair(?int $costCenterId, ?int $costElementId): ?int
    {
        if (! $costCenterId || ! $costElementId) {
            return null;
        }

        return static::query()->forPair($costCenterId, $costElementId)->value('id');
    }

    public function scopeSearch($query, ?string $term)
    {
        return $query->when(filled($term), function ($query) use ($term) {
            $query->where(function ($query) use ($term) {
                $query->where('code', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%");
            });
        });
    }

    /**
     * Batasi ke cost center (dan turunan segmennya bila diisi).
     */
    public function scopeForSegments($query, array $filters)
    {
        return $query
            ->when($filters['business_unit_id'] ?? null, fn ($q, $id) => $q->whereHas(
                'costCenter',
                fn ($cc) => $cc->where('erkap_business_unit_id', $id)
            ))
            ->when($filters['location_id'] ?? null, fn ($q, $id) => $q->whereHas(
                'costCenter',
                fn ($cc) => $cc->where('erkap_location_id', $id)
            ))
            ->when($filters['management_area_id'] ?? null, fn ($q, $id) => $q->whereHas(
                'costCenter',
                fn ($cc) => $cc->where('erkap_management_area_id', $id)
            ))
            ->when($filters['activity_id'] ?? null, fn ($q, $id) => $q->whereHas(
                'costCenter',
                fn ($cc) => $cc->where('erkap_activity_id', $id)
            ));
    }

    /* ---------------------------------------------------------------------
     | Kode & tampilan
     | ------------------------------------------------------------------ */

    /**
     * Kode COA tersusun dari kode Pusat Biaya (a..d) + kode elemen biaya (e).
     */
    public function composeCode(): ?string
    {
        $segments = $this->costCenter?->segments() ?? [];

        return CoaCode::compose([
            'business_unit' => $segments['business_unit'] ?? null,
            'location' => $segments['location'] ?? null,
            'management_area' => $segments['management_area'] ?? null,
            'activity' => $segments['activity'] ?? null,
            'cost_element' => $this->costElement?->code,
        ]);
    }

    public function syncComposedCode(): void
    {
        if ($this->cost_center_id && $this->cost_element_id) {
            $this->code = $this->composeCode() ?? $this->code;
        }
    }

    /**
     * Kode COA untuk tampilan: F-01-20200-110-6000.
     */
    public function getFormattedCodeAttribute(): string
    {
        return CoaCode::format((string) $this->code);
    }

    public function getSegmentedCodeAttribute(): array
    {
        return CoaCode::parse((string) $this->code);
    }

    /**
     * Label satu baris untuk tabel & export: kode tersegmentasi + nama.
     *
     * Dipakai laporan karena kode COA kini 15 karakter hasil komposisi a..e —
     * menampilkan `code` polos menghilangkan pembacaan segmen.
     */
    public function getLabelAttribute(): string
    {
        $name = (string) ($this->name ?? '');

        return $this->code ? $this->formatted_code.' - '.$name : ($name ?: '-');
    }
}
