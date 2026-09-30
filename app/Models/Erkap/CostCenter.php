<?php

namespace App\Models\Erkap;

use App\Models\ChartOfAccount;
use App\Models\Division;
use App\Models\Erkap\Traits\HasAuditTrail;
use App\Support\CoaCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostCenter extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = 'cost_centers';

    protected $fillable = ['code', 'erkap_business_unit_id', 'erkap_location_id', 'erkap_management_area_id', 'erkap_activity_id', 'is_swakelola', 'is_centralized', 'coordinating_division_id', 'name', 'owner', 'division_id', 'created_by', 'updated_by'];

    protected $casts = [
        'is_swakelola' => 'boolean',
        'is_centralized' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function (self $costCenter) {
            // Segmen diwarisi lebih dulu supaya `code` disusun dari rantai
            // yang benar, bukan dari input yang tidak konsisten.
            $costCenter->syncInheritedSegments();
            $costCenter->syncComposedColumns();
        });
    }

    /* ---------------------------------------------------------------------
     | Relasi segmen a..d
     | ------------------------------------------------------------------ */

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class, 'erkap_business_unit_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'erkap_location_id');
    }

    public function managementArea(): BelongsTo
    {
        return $this->belongsTo(ManagementArea::class, 'erkap_management_area_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'erkap_activity_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function coordinatingDivision(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'coordinating_division_id');
    }

    public function chartOfAccounts(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'cost_center_id');
    }

    public function routineCosts(): HasMany
    {
        return $this->hasMany(RoutineCost::class, 'cost_center_id');
    }

    /* ---------------------------------------------------------------------
     | Kode komposisi & segmen
     | ------------------------------------------------------------------ */

    /**
     * Kode Pusat Biaya tersusun dari segmen a..d hasil relasi.
     */
    public function composeCode(): ?string
    {
        return CoaCode::composeCostCenter([
            'business_unit' => optional($this->businessUnit)->code,
            'location' => optional($this->location)->code,
            'management_area' => optional($this->managementArea)->code,
            'activity' => optional($this->activity)->code,
        ]);
    }

    /**
     * Kode cost center untuk tampilan: F-01-20200-110.
     */
    public function getFormattedCodeAttribute(): string
    {
        return CoaCode::format((string) $this->code);
    }

    /**
     * Label satu baris untuk tabel & export: kode tersegmentasi + nama.
     */
    public function getLabelAttribute(): string
    {
        $name = (string) ($this->name ?? '');

        return $this->code ? $this->formatted_code.' - '.$name : ($name ?: '-');
    }

    /**
     * Segmen a..d sebagai array (kode => nilai), dipakai lookup & form.
     *
     * @return array<string, string|null>
     */
    public function segments(): array
    {
        return [
            'business_unit' => optional($this->businessUnit)->code,
            'location' => optional($this->location)->code,
            'management_area' => optional($this->managementArea)->code,
            'activity' => optional($this->activity)->code,
        ];
    }

    public function isSwakelola(): bool
    {
        if ($this->relationLoaded('activity') && $this->activity) {
            return (bool) $this->activity->is_swakelola;
        }

        return (bool) $this->is_swakelola;
    }

    public function isCentralized(): bool
    {
        return (bool) ($this->is_centralized ?? false);
    }

    /**
     * Elemen biaya yang tersedia pada Pusat Biaya ini (lewat Chart of Account).
     */
    public function costElements()
    {
        return $this->hasManyThrough(
            CostElement::class,
            ChartOfAccount::class,
            'cost_center_id',
            'id',
            'id',
            'cost_element_id'
        );
    }

    /**
     * Segmen a, b, dan c diwarisi dari Aktivitas (segmen d) karena Aktivitas
     * adalah daun rantai sehingga sudah menentukan seluruh leluhurnya.
     *
     * Pola ini mengikuti `Activity::syncInheritedSegments()` dan
     * `ManagementArea::booted()`, dan membuat kombinasi lintas segmen yang
     * tidak sah (mis. Lokasi dari unit lain) mustahil tersimpan.
     */
    public function syncInheritedSegments(): void
    {
        if (! $this->erkap_activity_id) {
            return;
        }

        $activity = $this->relationLoaded('activity') && $this->activity
            ? $this->activity
            : Activity::find($this->erkap_activity_id);

        $this->erkap_management_area_id = $activity?->erkap_management_area_id;
        $this->erkap_location_id = $activity?->erkap_location_id;
        $this->erkap_business_unit_id = $activity?->erkap_business_unit_id;
    }

    /**
     * Hitung ulang `code` & `is_swakelola` dari segmen terpilih.
     */
    public function syncComposedColumns(): void
    {
        $this->loadMissing(['businessUnit', 'location', 'managementArea', 'activity']);

        $this->code = $this->composeCode() ?? $this->code;

        if ($this->erkap_activity_id) {
            $this->is_swakelola = (bool) $this->activity?->is_swakelola;
        }
    }
}
