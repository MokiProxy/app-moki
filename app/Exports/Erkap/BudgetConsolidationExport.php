<?php

namespace App\Exports\Erkap;

use App\Models\Division;
use App\Models\Erkap\BudgetRealization;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RoutineCost;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BudgetConsolidationExport implements FromCollection, WithHeadings, WithMapping
{
    private const ROUTINE_MONTHS = [
        'jan_cost', 'feb_cost', 'mar_cost', 'apr_cost', 'may_cost', 'jun_cost',
        'jul_cost', 'aug_cost', 'sep_cost', 'oct_cost', 'nov_cost', 'des_cost',
    ];

    private const PLAN_MONTHS = [
        'jan_plan', 'feb_plan', 'mar_plan', 'apr_plan', 'may_plan', 'jun_plan',
        'jul_plan', 'aug_plan', 'sep_plan', 'oct_plan', 'nov_plan', 'dec_plan',
    ];

    protected $rkap;
    protected $year;
    protected $divisionId;
    protected $data;
    protected $row = 0;

    public function __construct(?RKAP $rkap, int $year, ?int $divisionId = null)
    {
        $this->rkap = $rkap;
        $this->year = $year;
        $this->divisionId = $divisionId;
        $this->data = $this->buildData();
    }

    public function exportData(): array
    {
        return $this->data;
    }

    public function collection(): Collection
    {
        $rows = collect();

        $components = [
            'Budget OPEX' => 'opex_budget',
            'Budget CAPEX' => 'capex_budget',
            'Total Budget' => 'total_budget',
            'Realisasi OPEX' => 'opex_realized',
            'Realisasi CAPEX' => 'capex_realized',
            'Total Realisasi' => 'total_realized',
            'Variance' => 'variance',
            'Variance %' => 'variance_percent',
        ];

        foreach ($components as $name => $key) {
            $rows->push([
                'row_type' => 'component',
                'name' => $name,
                'values' => array_map(fn ($month) => $month[$key], $this->data['monthlyRows']),
                'total' => $this->data['totals'][$key],
            ]);
        }

        foreach ($this->data['divisionRows'] as $divisionRow) {
            $rows->push([
                'row_type' => 'division',
                'name' => $divisionRow['division_name'] . ' - Budget',
                'values' => $divisionRow['monthly_budget'],
                'total' => $divisionRow['budget'],
            ]);
            $rows->push([
                'row_type' => 'division',
                'name' => $divisionRow['division_name'] . ' - Realisasi',
                'values' => $divisionRow['monthly_realized'],
                'total' => $divisionRow['realized'],
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Komponen', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des', 'Total',
        ];
    }

    public function map($row): array
    {
        if ($row['row_type'] === 'component') {
            $this->row++;

            $name = $row['name'] === 'Variance %' ? 'Variance %' : $this->row . '. ' . $row['name'];
        } else {
            $name = $row['name'];
        }

        return [$name, ...$row['values'], $row['total']];
    }

    protected function buildData(): array
    {
        $monthLabels = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        $routineCosts = RoutineCost::with('workProgram.riskIdentification.departmentTarget')
            ->when($this->divisionId, fn ($q) => $q->whereHas('workProgram.riskIdentification.departmentTarget', fn ($q2) => $q2->where('division_id', $this->divisionId)))
            ->when($this->rkap, fn ($q) => $q->whereHas('workProgram.riskIdentification.departmentTarget.companyTarget', fn ($q2) => $q2->where('erkap_rkap_id', $this->rkap->id)))
            ->get();

        $investmentPlans = InvestmentPlan::with('workProgram.riskIdentification.departmentTarget')
            ->when($this->divisionId, fn ($q) => $q->whereHas('workProgram.riskIdentification.departmentTarget', fn ($q2) => $q2->where('division_id', $this->divisionId)))
            ->when($this->rkap, fn ($q) => $q->whereHas('workProgram.riskIdentification.departmentTarget.companyTarget', fn ($q2) => $q2->where('erkap_rkap_id', $this->rkap->id)))
            ->get();

        $monthlyRows = [];
        $totals = array_fill_keys(['opex_budget', 'capex_budget', 'total_budget', 'opex_realized', 'capex_realized', 'total_realized', 'variance', 'variance_percent'], 0.0);

        for ($index = 0; $index < 12; $index++) {
            $monthData = [
                'opex_budget' => (float) $routineCosts->sum(self::ROUTINE_MONTHS[$index]),
                'capex_budget' => (float) $investmentPlans->sum(self::PLAN_MONTHS[$index]),
            ];
            $monthData['total_budget'] = $monthData['opex_budget'] + $monthData['capex_budget'];

            $realizations = BudgetRealization::where('year', $this->year)
                ->where('month', $index + 1)
                ->when($this->rkap, fn ($q) => $q->where('erkap_rkap_id', $this->rkap->id))
                ->get();

            $monthData['opex_realized'] = (float) $realizations->where('erkap_routine_cost_id', '!==', null)->sum('realized');
            $monthData['capex_realized'] = (float) $realizations->where('erkap_investment_plan_id', '!==', null)->sum('realized');
            $monthData['total_realized'] = $monthData['opex_realized'] + $monthData['capex_realized'];
            $monthData['variance'] = $monthData['total_realized'] - $monthData['total_budget'];
            $monthData['variance_percent'] = $monthData['total_budget'] > 0
                ? round((($monthData['total_realized'] - $monthData['total_budget']) / $monthData['total_budget']) * 100, 2)
                : 0;

            foreach ($totals as $key => $value) {
                $totals[$key] = $value + $monthData[$key];
            }

            $monthlyRows[] = $monthData;
        }

        $totals['variance_percent'] = $totals['total_budget'] > 0
            ? round(($totals['total_realized'] - $totals['total_budget']) / $totals['total_budget'] * 100, 2)
            : 0;

        return [
            'rkap' => $this->rkap,
            'year' => $this->year,
            'division_name' => $this->divisionId ? (Division::find($this->divisionId)?->name ?? '-') : 'Semua Divisi',
            'monthLabels' => $monthLabels,
            'monthlyRows' => $monthlyRows,
            'divisionRows' => $this->divisionRows($routineCosts, $investmentPlans),
            'totals' => $totals,
        ];
    }

    protected function divisionRows($routineCosts, $investmentPlans): array
    {
        $budgetByDivision = [];

        foreach ($routineCosts as $cost) {
            $divisionId = $this->divisionIdOf($cost->workProgram);

            foreach (self::ROUTINE_MONTHS as $index => $column) {
                $budgetByDivision[$divisionId]['monthly_budget'][$index] = ($budgetByDivision[$divisionId]['monthly_budget'][$index] ?? 0) + (float) $cost->{$column};
            }

            $budgetByDivision[$divisionId]['budget'] = ($budgetByDivision[$divisionId]['budget'] ?? 0) + (float) $cost->total;
        }

        foreach ($investmentPlans as $plan) {
            $divisionId = $this->divisionIdOf($plan->workProgram);

            foreach (self::PLAN_MONTHS as $index => $column) {
                $budgetByDivision[$divisionId]['monthly_budget'][$index] = ($budgetByDivision[$divisionId]['monthly_budget'][$index] ?? 0) + (float) $plan->{$column};
            }

            $budgetByDivision[$divisionId]['budget'] = ($budgetByDivision[$divisionId]['budget'] ?? 0) + (float) $plan->total;
        }

        $realizations = BudgetRealization::with('routineCost.workProgram.riskIdentification.departmentTarget', 'investmentPlan.workProgram.riskIdentification.departmentTarget')
            ->where('year', $this->year)
            ->when($this->rkap, fn ($q) => $q->where('erkap_rkap_id', $this->rkap->id))
            ->get();

        foreach ($realizations as $realization) {
            $workProgram = $realization->routineCost?->workProgram ?? $realization->investmentPlan?->workProgram;
            $divisionId = $this->divisionIdOf($workProgram);
            $index = max(0, min(11, (int) $realization->month - 1));

            $budgetByDivision[$divisionId]['monthly_realized'][$index] = ($budgetByDivision[$divisionId]['monthly_realized'][$index] ?? 0) + (float) $realization->realized;
            $budgetByDivision[$divisionId]['realized'] = ($budgetByDivision[$divisionId]['realized'] ?? 0) + (float) $realization->realized;
        }

        $divisionNames = Division::pluck('name', 'id')->all();
        $rows = [];

        foreach ($budgetByDivision as $divisionId => $item) {
            if ($divisionId === null) {
                continue;
            }

            $rows[] = [
                'division_id' => $divisionId,
                'division_name' => $divisionNames[$divisionId] ?? '-',
                'monthly_budget' => $item['monthly_budget'] ?? array_fill(0, 12, 0),
                'monthly_realized' => $item['monthly_realized'] ?? array_fill(0, 12, 0),
                'budget' => $item['budget'] ?? 0,
                'realized' => $item['realized'] ?? 0,
            ];
        }

        usort($rows, fn ($a, $b) => $a['division_name'] <=> $b['division_name']);

        return $rows;
    }

    protected function divisionIdOf($workProgram): ?int
    {
        return $workProgram?->riskIdentification?->departmentTarget?->division_id;
    }
}