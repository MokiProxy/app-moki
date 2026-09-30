<?php

namespace App\Models\Erkap;

use App\Models\ChartOfAccount;
use App\Models\Erkap\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostElement extends Model
{
    use HasFactory, HasAuditTrail;

    protected $table = 'erkap_cost_elements';

    protected $fillable = ['code', 'name', 'erkap_cost_element_category_id', 'chart_of_account_id'];

    public function costElementCategory()
    {
        return $this->belongsTo(CostElementCategory::class, 'erkap_cost_element_category_id');
    }

    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    /**
     * Seluruh Chart of Account yang memakai elemen biaya ini
     * (satu per Pusat Biaya, hasil komposisi a..e).
     */
    public function chartOfAccounts(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'cost_element_id');
    }

    public function routineCosts()
    {
        return $this->hasMany(RoutineCost::class, 'erkap_cost_element_id');
    }

    /**
     * Label satu baris untuk tabel, export, dan widget: kode elemen + nama.
     */
    public function getLabelAttribute(): string
    {
        $name = (string) ($this->name ?? '');

        return $this->code ? $this->code.' - '.$name : ($name ?: '-');
    }

    /**
     * Chart of Account yang disarankan untuk elemen biaya ini: tautan eksplisit
     * bila ada, jika tidak ambil COA pertama yang memakainya.
     */
    public function coaSuggestion(): ?ChartOfAccount
    {
        if ($this->chart_of_account_id) {
            return $this->chartOfAccount;
        }

        return $this->chartOfAccounts()->orderBy('code')->first();
    }
}
