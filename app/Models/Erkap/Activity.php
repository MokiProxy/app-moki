<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use App\Support\CoaCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    use HasFactory, HasAuditTrail;

    /** Aktivitas biaya terpusat (gaji, TI) — dipakai Pusat Biaya terkoordinasi. */
    public const CENTRALIZED_CODES = ['130', '230'];

    protected $table = 'erkap_activities';

    protected $fillable = ['code', 'name', 'description', 'erkap_management_area_id', 'erkap_location_id', 'erkap_business_unit_id', 'is_swakelola', 'is_active', 'sort_order', 'created_by', 'updated_by'];

    protected $casts = [
        'is_swakelola' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted()
    {
        static::saving(function (self $activity) {
            $activity->syncInheritedSegments();
        });
    }

    public function managementArea(): BelongsTo
    {
        return $this->belongsTo(ManagementArea::class, 'erkap_management_area_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'erkap_location_id');
    }

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class, 'erkap_business_unit_id');
    }

    public function costCenters(): HasMany
    {
        return $this->hasMany(CostCenter::class, 'erkap_activity_id');
    }

    /**
     * Kode Pusat Biaya yang memakai aktivitas ini (segmen a..d).
     */
    public function composeCode(): ?string
    {
        return CoaCode::composeCostCenter([
            'business_unit' => $this->businessUnit?->code,
            'location' => $this->location?->code,
            'management_area' => $this->managementArea?->code,
            'activity' => $this->code,
        ]);
    }

    public function isCentralizedActivity(): bool
    {
        return in_array($this->code, self::CENTRALIZED_CODES, true);
    }

    /**
     * Warisi segmen leluhur agar filter berjenjang tidak perlu join.
     */
    public function syncInheritedSegments(): void
    {
        if (! $this->erkap_management_area_id) {
            return;
        }

        $area = $this->managementArea ?: ManagementArea::find($this->erkap_management_area_id);

        $this->erkap_location_id = $area?->erkap_location_id;
        $this->erkap_business_unit_id = $area?->erkap_business_unit_id;
    }
}
