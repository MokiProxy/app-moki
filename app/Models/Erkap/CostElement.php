<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use App\Models\ChartOfAccount;
use App\Support\CoaCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function routineCosts()
    {
        return $this->hasMany(RoutineCost::class, 'erkap_cost_element_id');
    }

    /**
     * Suggest the Chart of Account that matches this cost element's code after
     * the 16-digit backfill, falling back to the existing explicit link.
     */
    public function coaSuggestion(): ?ChartOfAccount
    {
        if ($this->chart_of_account_id) {
            return $this->chartOfAccount;
        }

        if (! $paddedCode = CoaCode::pad($this->code)) {
            return null;
        }

        $chartOfAccount = ChartOfAccount::where('code', $paddedCode)->first();

        if (! $chartOfAccount && preg_match('/^\d+$/', $this->code)) {
            $chartOfAccount = ChartOfAccount::where('code', 'like', $this->code . '%')
                ->orderBy('code')
                ->first();
        }

        return $chartOfAccount;
    }
}
