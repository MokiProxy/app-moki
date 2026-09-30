<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = 'erkap_locations';

    protected $fillable = ['code', 'name', 'description', 'erkap_business_unit_id', 'is_active', 'sort_order', 'created_by', 'updated_by'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class, 'erkap_business_unit_id');
    }

    public function managementAreas(): HasMany
    {
        return $this->hasMany(ManagementArea::class, 'erkap_location_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'erkap_location_id');
    }

    public function costCenters(): HasMany
    {
        return $this->hasMany(CostCenter::class, 'erkap_location_id');
    }
}
