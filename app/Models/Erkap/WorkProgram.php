<?php

namespace App\Models\Erkap;

use App\Enums\ErkapRatingLevel;
use App\Models\Erkap\Traits\HasAuditTrail;
use App\Models\Erkap\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class WorkProgram extends Model
{
    use HasFactory, HasApprovalWorkflow, HasAuditTrail;

    public const MONTH_COLUMNS = [
        'jan_plan', 'feb_plan', 'mar_plan', 'apr_plan', 'may_plan', 'jun_plan',
        'jul_plan', 'aug_plan', 'sep_plan', 'oct_plan', 'nov_plan', 'dec_plan',
    ];

    protected $table = 'erkap_work_programs';

    protected $fillable = [
        'erkap_risk_identification_id',
        'code',
        'name',
        'units',
        'year_plan',
        'jan_plan',
        'feb_plan',
        'mar_plan',
        'apr_plan',
        'may_plan',
        'jun_plan',
        'jul_plan',
        'aug_plan',
        'sep_plan',
        'oct_plan',
        'nov_plan',
        'dec_plan',
        'depends_on_work_program_id',
        'status',
    ];

    public function riskIdentification()
    {
        return $this->belongsTo(RiskIdentification::class, 'erkap_risk_identification_id');
    }

    public function dependsOn()
    {
        return $this->belongsTo(WorkProgram::class, 'depends_on_work_program_id');
    }

    public function dependents()
    {
        return $this->hasMany(WorkProgram::class, 'depends_on_work_program_id');
    }

    public function routineCosts()
    {
        return $this->hasMany(RoutineCost::class, 'erkap_work_program_id');
    }

    public function investmentPlans()
    {
        return $this->hasMany(InvestmentPlan::class, 'erkap_work_program_id');
    }

    public function hasBudget(): bool
    {
        return $this->routineCosts()->count() > 0 || $this->investmentPlans()->count() > 0;
    }

    public function realizations()
    {
        return $this->hasMany(ProgramRealization::class, 'erkap_work_program_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $program) {
            $minRating = $program->riskIdentification?->departmentTarget?->ratingCriteria?->rating ?? '';
            $validRatings = ErkapRatingLevel::allowedForWorkProgram();
            
            if (!in_array($minRating, $validRatings, true)) {
                throw ValidationException::withMessages([
                    'rating' => 'Program Kerja hanya bisa dibuat untuk Sasaran dengan Rating A ke atas',
                ]);
            }

            if (! $program->riskIdentification?->hasStrategy()) {
                throw ValidationException::withMessages([
                    'risk_strategy' => 'Program Kerja hanya bisa dibuat untuk Risiko yang sudah memiliki Strategi Mitigasi',
                ]);
            }
        });
    }

    public function canSubmitForApproval(): bool
    {
        if (! $this->hasBudget()) {
            throw ValidationException::withMessages([
                'budget' => 'Program Kerja wajib memiliki anggaran (Form 3 atau Form 4) sebelum disetujui',
            ]);
        }

        $this->validateMonthlyBreakdown();

        return true;
    }

    public function validateMonthlyBreakdown(): void
    {
        $monthlySum = collect(self::MONTH_COLUMNS)->sum(fn ($month) => (float) ($this->$month ?? 0));
        $yearPlan = (float) ($this->year_plan ?? 0);

        if ($yearPlan > 0 && abs($monthlySum - $yearPlan) > 0.01) {
            throw ValidationException::withMessages([
                'monthly_breakdown' => 'Total bulanan harus sama dengan target tahunan',
            ]);
        }
    }

    public function monthlyCumulativePercents(): array
    {
        $total = (float) ($this->year_plan ?? 0);
        $running = 0.0;
        $result = [];

        foreach (self::MONTH_COLUMNS as $month) {
            $running += (float) ($this->$month ?? 0);
            $result[$month] = $total > 0 ? round(($running / $total) * 100, 2) : 0.0;
        }

        return $result;
    }

    public function cumulativePercentAt(string $month): float
    {
        return $this->monthlyCumulativePercents()[$month] ?? 0.0;
    }
}
