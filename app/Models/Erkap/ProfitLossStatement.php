<?php

namespace App\Models\Erkap;

use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfitLossStatement extends Model
{
    use HasFactory;

    protected $table = 'erkap_profit_loss_statements';

    protected $fillable = [
        'erkap_rkap_id',
        'division_id',
        'period',
        'month',
        'quarter',
        'total_revenue',
        'total_expense',
        'gross_profit',
        'operating_expense',
        'operating_profit',
        'other_income',
        'other_expense',
        'profit_before_tax',
        'tax',
        'net_profit',
        'margin',
    ];

    public function rkap()
    {
        return $this->belongsTo(RKAP::class, 'erkap_rkap_id');
    }

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function calculateMargin()
    {
        if ($this->total_revenue > 0) {
            $this->margin = ($this->net_profit / $this->total_revenue) * 100;
        } else {
            $this->margin = 0;
        }

        $this->save();

        return $this;
    }
}
