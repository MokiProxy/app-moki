<?php

namespace App\Exports\Erkap;

use App\Models\Erkap\BudgetCapex;
use App\Models\Erkap\BudgetRealization;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RiskAssessmentMonthly;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RoutineCost;
use App\Models\Erkap\WorkProgram;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class ConsolidatedRkapExport implements WithMultipleSheets
{
    protected $rkap;
    protected $year;
    protected $divisionId;

    public function __construct(?RKAP $rkap, ?int $year = null, ?int $divisionId = null)
    {
        $this->rkap = $rkap;
        $this->year = $year ?? (int) ($rkap?->year ?? date('Y'));
        $this->divisionId = $divisionId;
    }

    public function sheets(): array
    {
        $rkEmit = $this->rkap ? $this->rkap->id : null;

        return [
            new class($rkEmit, $this->divisionId) extends RiskIdentificationExportSheet
            {
                public function __construct(?int $rkapId, ?int $divisionId)
                {
                    parent::__construct(RiskIdentification::query()
                        ->with('departmentTarget.division', 'riskType', 'riskTaxonomy')
                        ->when($rkapId, fn ($q) => $q->whereHas('departmentTarget.companyTarget', fn ($q2) => $q2->where('erkap_rkap_id', $rkapId)))
                        ->when($divisionId, fn ($q) => $q->whereHas('departmentTarget', fn ($q2) => $q2->where('division_id', $divisionId)))
                        ->get(), 'Form 1');
                }
            },

            new class($rkEmit, $this->divisionId) extends RiskIdentificationExportSheet
            {
                public function __construct(?int $rkapId, ?int $divisionId)
                {
                    parent::__construct(WorkProgram::query()
                        ->with('riskIdentification.departmentTarget.division')
                        ->when($rkapId, fn ($q) => $q->whereHas('riskIdentification.departmentTarget.companyTarget', fn ($q2) => $q2->where('erkap_rkap_id', $rkapId)))
                        ->when($divisionId, fn ($q) => $q->whereHas('riskIdentification.departmentTarget', fn ($q2) => $q2->where('division_id', $divisionId)))
                        ->get(), 'Form 2');
                }
            },

            new class($rkEmit, $this->divisionId) extends RiskIdentificationExportSheet
            {
                public function __construct(?int $rkapId, ?int $divisionId)
                {
                    parent::__construct(RoutineCost::query()
                        ->with('workProgram.riskIdentification.departmentTarget.division', 'costElement', 'costCenter')
                        ->when($rkapId, fn ($q) => $q->whereHas('workProgram.riskIdentification.departmentTarget.companyTarget', fn ($q2) => $q2->where('erkap_rkap_id', $rkapId)))
                        ->when($divisionId, fn ($q) => $q->whereHas('workProgram.riskIdentification.departmentTarget', fn ($q2) => $q2->where('division_id', $divisionId)))
                        ->get(), 'Form 3');
                }
            },

            new class($rkEmit, $this->divisionId) extends RiskIdentificationExportSheet
            {
                public function __construct(?int $rkapId, ?int $divisionId)
                {
                    parent::__construct(InvestmentPlan::query()
                        ->with('workProgram.riskIdentification.departmentTarget.division', 'investattionCategory')
                        ->when($rkapId, fn ($q) => $q->whereHas('workProgram.riskIdentification.departmentTarget.companyTarget', fn ($q2) => $q2->where('erkap_rkap_id', $rkapId)))
                        ->when($divisionId, fn ($q) => $q->whereHas('workProgram.riskIdentification.departmentTarget', fn ($q2) => $q2->where('division_id', $divisionId)))
                        ->get(), 'Form 4');
                }
            },

            new class($rkEmit, $this->divisionId, $this->year) extends RiskIdentificationExportSheet
            {
                public function __construct(?int $rkapId, ?int $divisionId, ?int $year)
                {
                    parent::__construct(BudgetCapex::query()
                        ->with('rkap', 'division')
                        ->when($rkapId, fn ($q) => $q->where('erkap_rkap_id', $rkapId))
                        ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId))
                        ->get(), 'Form 5');
                }
            },

            new class($rkEmit, $this->year) extends RiskIdentificationExportSheet
            {
                public function __construct(?int $rkapId, ?int $year)
                {
                    parent::__construct(RiskAssessmentMonthly::query()
                        ->with('riskIdentification.departmentTarget.division', 'riskAppetite')
                        ->where('year', $year)
                        ->when($rkapId, fn ($q) => $q->whereHas('riskIdentification.departmentTarget.companyTarget', fn ($q2) => $q2->where('erkap_rkap_id', $rkapId)))
                        ->orderBy('month')
                        ->get(), 'Form 6');
                }
            },

            new class($rkEmit, $this->divisionId, $this->year) extends RiskIdentificationExportSheet
            {
                public function __construct(?int $rkapId, ?int $divisionId, ?int $year)
                {
                    parent::__construct(RKAP::query()
                        ->when($rkapId, fn ($q) => $q->where('id', $rkapId))
                        ->get(), 'Konsolidasi RKAP');
                }
            },

            new class($rkEmit, $this->divisionId, $this->year) extends RiskIdentificationExportSheet
            {
                public function __construct(?int $rkapId, ?int $divisionId, ?int $year)
                {
                    parent::__construct(BudgetRealization::query()
                        ->with('routineCost.workProgram.riskIdentification.departmentTarget.division', 'investmentPlan.workProgram')
                        ->where('year', $year)
                        ->when($rkapId, fn ($q) => $q->where('erkap_rkap_id', $rkapId))
                        ->get(), 'Realisasi (BvA)');
                }
            },
        ];
    }
}

abstract class RiskIdentificationExportSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $rows;
    protected $title;
    protected $row = 0;

    public function __construct(Collection $rows, string $title)
    {
        $this->rows = $rows;
        $this->title = $title;
    }

    public function title(): string
    {
        return mb_substr($this->title, 0, 31);
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['No', 'Uraian', 'Divisi', 'Status'];
    }

    public function map($row): array
    {
        $this->row++;

        $name = $this->resolveName($row);

        return [
            $this->row,
            is_array($name) ? json_encode($name) : (string) $name,
            $this->divisionOf($row),
            $this->statusOf($row),
        ];
    }

    protected function resolveName($row): mixed
    {
        foreach (['name', 'need', 'kpi_name', 'risk', 'description', 'notes', 'target', 'objectives'] as $attribute) {
            if (isset($row->{$attribute}) && ! is_array($row->{$attribute})) {
                return $row->{$attribute};
            }
        }

        $monthly = collect($row->getAttributes() ?? [])->map(function ($value, $key) {
            return str_ends_with($key, '_plan') || str_ends_with($key, '_cost')
                ? (float) ($value ?? 0)
                : 0;
        })->sum();

        return $monthly > 0 ? 'Program ' . $this->row : '-';
    }

    protected function divisionOf($row): string
    {
        foreach (['division', 'departmentTarget.division', 'workProgram.riskIdentification.departmentTarget.division', 'riskIdentification.departmentTarget.division', 'routineCost.workProgram.riskIdentification.departmentTarget.division', 'investmentPlan.workProgram.riskIdentification.departmentTarget.division'] as $relation) {
            $value = data_get($row, $relation);

            if ($value && (is_object($value) || is_array($value))) {
                $value = is_array($value) ? ($value['name'] ?? null) : ($value->name ?? null);
            }

            if ($value) {
                return $value;
            }
        }

        return '-';
    }

    protected function statusOf($row): string
    {
        if (method_exists($row, 'statusLabel')) {
            return $row->statusLabel();
        }

        return $row->status ?? $row->month ?? '-';
    }
}