<?php

namespace App\Models\Erkap;

use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostCenter extends Model
{
    use HasFactory;

    protected $table = 'cost_centers';

    protected $fillable = ['code', 'is_swakelola', 'name', 'owner', 'division_id', 'created_by', 'updated_by'];

    protected $casts = [
        'is_swakelola' => 'boolean',
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function costElementCode(): ?string
    {
        return strlen($this->code) >= 4 ? substr($this->code, -4) : null;
    }

    public function costCenterCode(): ?string
    {
        return strlen($this->code) >= 7 ? substr($this->code, -7, 3) : null;
    }

    public function isSwakelola(): bool
    {
        return $this->is_swakelola ?? $this->costCenterCode() === '510';
    }

    public function costElement()
    {
        $code = $this->costElementCode();

        return $code ? CostElement::where('code', $code)->first() : null;
    }

    public function routineCosts(): HasMany
    {
        return $this->hasMany(RoutineCost::class, 'cost_center_id');
    }
}