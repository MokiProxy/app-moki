<?php

namespace App\Models\Erkap;

use App\Models\Erkap\Traits\HasAuditTrail;
use App\Models\Erkap\Traits\HasApprovalWorkflow;
use App\Models\ChartOfAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InvestmentPlan extends Model
{
    use HasFactory, HasApprovalWorkflow, HasAuditTrail;

    protected $table = 'erkap_investment_plans';

    protected $fillable = [
        'erkap_work_program_id',
        'cost_center_id',
        'chart_of_account_id',
        'erkap_investattion_category_id',
        'erkap_investation_type_id',
        'erkap_investation_criteria_id',
        'name',
        'description',
        'unit',
        'qty',
        'unit_price',
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
        'total',
        'prior_year_amount',
        'is_kumulatif',
        'priority_order',
        'status',
        'proposal_file_path',
        'proposal_original_name',
        'cba_json',
        'cba_attachment_path',
        'gate_review_status',
    ];

    protected $casts = [
        'is_kumulatif' => 'boolean',
        'priority_order' => 'integer',
        'cost_center_id' => 'integer',
        'chart_of_account_id' => 'integer',
        'cba_json' => 'array',
    ];

    public function workProgram()
    {
        return $this->belongsTo(WorkProgram::class, 'erkap_work_program_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    public function stageGates()
    {
        return $this->hasMany(InvestmentStageGate::class, 'erkap_investment_plan_id')->orderBy('stage_order');
    }

    public function investattionCategory()
    {
        return $this->belongsTo(InvestattionCategory::class, 'erkap_investattion_category_id');
    }

    public function investationType()
    {
        return $this->belongsTo(InvestationType::class, 'erkap_investation_type_id');
    }

    public function investationCriteria()
    {
        return $this->belongsTo(InvestationCriteria::class, 'erkap_investation_criteria_id');
    }

    public function budgetRealizations()
    {
        return $this->hasMany(BudgetRealization::class, 'erkap_investment_plan_id');
    }

    public function validateMonthlyBreakdown(): void
    {
        $months = ['jan_plan', 'feb_plan', 'mar_plan', 'apr_plan', 'may_plan', 'jun_plan', 'jul_plan', 'aug_plan', 'sep_plan', 'oct_plan', 'nov_plan', 'dec_plan'];
        $monthlySum = collect($months)->sum(fn ($month) => (float) ($this->$month ?? 0));
        $total = (float) ($this->total ?? 0);

        if ($total > 0 && abs($monthlySum - $total) > 0.01) {
            throw ValidationException::withMessages([
                'monthly_breakdown' => 'Total bulanan harus sama dengan total investasi',
            ]);
        }
    }

    public function validatePaymentSchedule(): void
    {
        if ($this->is_kumulatif) {
            return;
        }

        $months = ['jan_plan', 'feb_plan', 'mar_plan', 'apr_plan', 'may_plan', 'jun_plan', 'jul_plan', 'aug_plan', 'sep_plan', 'oct_plan', 'nov_plan', 'dec_plan'];
        $monthlySum = collect($months)->sum(fn ($month) => (float) ($this->$month ?? 0));
        $total = (float) ($this->total ?? 0);

        if ($total > 0 && abs($monthlySum - $total) > 0.01) {
            throw ValidationException::withMessages([
                'payment_schedule' => 'Jadwal pembayaran bulanan harus sama dengan total pembayaran.',
            ]);
        }
    }

    public function hasProposal(): bool
    {
        return filled($this->proposal_file_path);
    }

    public function hasCba(): bool
    {
        return filled($this->cba_json) || filled($this->cba_attachment_path);
    }

    public function isGateComplete(): bool
    {
        return $this->stageGates()->count() > 0
            && $this->stageGates()->where('status', '!=', 'approved')->count() === 0;
    }

    public function currentGate(): ?InvestmentStageGate
    {
        return $this->stageGates()->pending()->orderBy('stage_order')->first();
    }

    public function proposalUrl(): ?string
    {
        return $this->proposal_file_path
            ? Storage::disk('public')->url($this->proposal_file_path)
            : null;
    }

    public function gateReviewStatusLabel(): string
    {
        return match ($this->gate_review_status) {
            'approved' => 'Gate Disetujui',
            'partial' => 'Gate Disetujui Sebagian',
            'rejected' => 'Gate Ditolak',
            'in_review' => 'Dalam Review',
            default => 'Belum Ada Gate',
        };
    }

    public function gateReviewStatusClass(): string
    {
        return match ($this->gate_review_status) {
            'approved' => 'success',
            'partial' => 'primary',
            'rejected' => 'danger',
            'in_review' => 'info',
            default => 'secondary',
        };
    }

    public function canBeSubmitted(): bool
    {
        return in_array($this->status, ['draft', 'rejected'], true) && $this->hasProposal();
    }
}
