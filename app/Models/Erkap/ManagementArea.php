<?php

namespace App\Models\Erkap;

use App\Models\Division;
use App\Models\Erkap\Traits\HasAuditTrail;
use App\Support\CoaCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManagementArea extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = 'erkap_management_areas';

    protected $fillable = ['code', 'name', 'description', 'erkap_location_id', 'erkap_business_unit_id', 'division_id', 'is_active', 'sort_order', 'created_by', 'updated_by'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted()
    {
        static::saving(function (self $area) {
            if ($area->erkap_location_id) {
                $area->erkap_business_unit_id = $area->relationLoaded('location') && $area->location
                    ? $area->location->erkap_business_unit_id
                    : Location::find($area->erkap_location_id)?->erkap_business_unit_id;
            }
        });
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'erkap_location_id');
    }

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class, 'erkap_business_unit_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'erkap_management_area_id');
    }

    public function costCenters(): HasMany
    {
        return $this->hasMany(CostCenter::class, 'erkap_management_area_id');
    }

    /**
     * Segmen a..c dari area ini.
     *
     * @return array<string, string|null>
     */
    public function segments(): array
    {
        return [
            'business_unit' => $this->businessUnit?->code,
            'location' => $this->location?->code,
            'management_area' => $this->code,
        ];
    }

    public function composeCode(?string $activityCode): ?string
    {
        return CoaCode::composeCostCenter($this->segments() + ['activity' => $activityCode]);
    }
}
