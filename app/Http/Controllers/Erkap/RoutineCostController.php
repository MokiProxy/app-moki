<?php

namespace App\Http\Controllers\Erkap;

use App\Exports\Erkap\RoutineCostExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoutineCostRequest;
use App\Http\Requests\UpdateRoutineCostRequest;
use App\Models\ChartOfAccount;
use App\Models\Division;
use App\Models\Erkap\BudgetRealization;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\CostElement;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RoutineCost;
use App\Models\Erkap\WorkProgram;
use App\Services\ApprovalService;
use App\Services\Erkap\CentralizedCostService;
use App\Services\ErkapAccess;
use App\Services\Erkap\RKAPLifecycleService;
use App\Services\Erkap\ZBBReviewService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class RoutineCostController extends Controller
{
    private const MONTHS = [
        'jan_cost', 'feb_cost', 'mar_cost', 'apr_cost', 'may_cost', 'jun_cost',
        'jul_cost', 'aug_cost', 'sep_cost', 'oct_cost', 'nov_cost', 'des_cost',
    ];

    public function index()
    {
        $pageName = 'Biaya Rutin';
        $lockedRkaps = RKAP::lockedForInput()->orderByDesc('year')->get();
        $routineCosts = RoutineCost::with(['workProgram', 'costElement', 'costCenter.coordinatingDivision', 'chartOfAccount'])
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->whereIn('erkap_work_program_id', ErkapAccess::workProgramIds());
            })
            ->paginate(10);

        $baseQuery = RoutineCost::query()
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->whereIn('erkap_work_program_id', ErkapAccess::workProgramIds());
            });

        $subtotalByElement = (clone $baseQuery)
            ->select('erkap_cost_element_id', DB::raw('SUM(total) as subtotal'))
            ->with('costElement')
            ->groupBy('erkap_cost_element_id')
            ->get();

        $subtotalByProgram = (clone $baseQuery)
            ->select('erkap_work_program_id', DB::raw('SUM(total) as subtotal'))
            ->with('workProgram')
            ->groupBy('erkap_work_program_id')
            ->get();

        $subtotalByCostCenter = (clone $baseQuery)
            ->select('cost_center_id', 'cost_center_owner', DB::raw('SUM(total) as subtotal'))
            ->with('costCenter')
            ->groupBy('cost_center_id', 'cost_center_owner')
            ->get();

        $grandTotal = (clone $baseQuery)->sum('total');

        $submittableCount = RoutineCost::query()
            ->whereIn('erkap_work_program_id', ErkapAccess::workProgramIds())
            ->whereIn('status', ['draft', 'rejected'])
            ->count();

        return view(
            'erkap.routine-cost.index',
            compact('pageName', 'routineCosts', 'subtotalByElement', 'subtotalByProgram', 'subtotalByCostCenter', 'grandTotal', 'submittableCount', 'lockedRkaps')
        );
    }

    public function export()
    {
        $routineCosts = RoutineCost::with(['workProgram', 'costElement', 'costCenter.coordinatingDivision', 'chartOfAccount'])
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->whereIn('erkap_work_program_id', ErkapAccess::workProgramIds());
            })
            ->orderBy('id')
            ->get();

        return Excel::download(new RoutineCostExport($routineCosts), 'biaya-rutin-' . date('Y-m-d-Hi') . '.xlsx');
    }

    public function exportPdf()
    {
        $routineCosts = RoutineCost::with(['workProgram', 'costElement', 'costCenter.coordinatingDivision', 'chartOfAccount'])
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->whereIn('erkap_work_program_id', ErkapAccess::workProgramIds());
            })
            ->orderBy('id')
            ->get();

        $pdf = Pdf::loadView('erkap.exports.routine-cost-pdf', compact('routineCosts'));
        $pdf->setOption('isRemoteEnabled', true);

        return $pdf->download('biaya-rutin-' . date('Y-m-d-Hi') . '.pdf');
    }

    public function create()
    {
        $pageName = 'Buat Biaya Rutin';
        $workPrograms = WorkProgram::whereIn('id', ErkapAccess::workProgramIds())->get();
        $costElements = CostElement::with('chartOfAccount')->get();
        $chartOfAccounts = ChartOfAccount::expense()->orderBy('code')->get();
        [$swakelolaCostCenters, $nonSwakelolaCostCenters] = $this->groupedCostCenters($costElements);

        $subtotalByProgram = $this->subtotalMap('erkap_work_program_id');
        $subtotalByElement = $this->subtotalMap('erkap_cost_element_id');
        $subtotalByCostCenter = $this->subtotalByCostCenterMap();
        $budgetPreview = $this->budgetPreviewByProgram();

        return view(
            'erkap.routine-cost.create',
            compact('pageName', 'workPrograms', 'costElements', 'chartOfAccounts', 'swakelolaCostCenters', 'nonSwakelolaCostCenters', 'subtotalByProgram', 'subtotalByElement', 'subtotalByCostCenter', 'budgetPreview')
        );
    }

    public function store(StoreRoutineCostRequest $request)
    {
        try {
            ErkapAccess::assertWorkProgramAccess($request->integer('erkap_work_program_id'));
            RKAPLifecycleService::assertNotLocked(
                RKAPLifecycleService::resolveForWorkProgram($request->integer('erkap_work_program_id')),
                'Biaya rutin'
            );

            $data = $request->validated();
            $data['chart_of_account_id'] = $this->resolveChartOfAccountId($data);
            $data['is_kumulatif'] = $request->boolean('is_kumulatif');

            $this->assertCentralizedCostInput($data, $request->integer('erkap_work_program_id'));

            if (! $this->validateTotal($data, $data['is_kumulatif'])) {
                return redirect()->route('erkap.routine-costs.create')
                    ->withInput()
                    ->withErrors(['total' => $data['is_kumulatif']
                        ? 'Total harus sama dengan qty × harga satuan.'
                        : 'Total harus sama dengan qty × harga satuan dan jumlah seluruh bulanan.']);
            }

            $data['total'] = $data['is_kumulatif']
                ? (float) $data['qty'] * (float) $data['unit_price']
                : $this->sumMonths($data);

            RoutineCost::create($data);

            $this->rebuildZbb($request->integer('erkap_work_program_id'));

            return redirect()->route('erkap.routine-costs.index')
                ->with('success', 'Biaya rutin baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.routine-costs.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(RoutineCost $routineCost)
    {
        ErkapAccess::assertWorkProgramAccess($routineCost->erkap_work_program_id);

        $pageName = 'Edit Biaya Rutin';
        $workPrograms = WorkProgram::whereIn('id', ErkapAccess::workProgramIds())->get();
        $costElements = CostElement::with('chartOfAccount')->get();
        $chartOfAccounts = ChartOfAccount::expense()->orderBy('code')->get();
        [$swakelolaCostCenters, $nonSwakelolaCostCenters] = $this->groupedCostCenters($costElements);

        $subtotalByProgram = $this->subtotalMap('erkap_work_program_id');
        $subtotalByElement = $this->subtotalMap('erkap_cost_element_id');
        $subtotalByCostCenter = $this->subtotalByCostCenterMap();
        $budgetPreview = $this->budgetPreviewByProgram();

        return view(
            'erkap.routine-cost.edit',
            compact('pageName', 'routineCost', 'workPrograms', 'costElements', 'chartOfAccounts', 'swakelolaCostCenters', 'nonSwakelolaCostCenters', 'subtotalByProgram', 'subtotalByElement', 'subtotalByCostCenter', 'budgetPreview')
        );
    }

    public function update(UpdateRoutineCostRequest $request, RoutineCost $routineCost)
    {
        try {
            ErkapAccess::assertWorkProgramAccess($routineCost->erkap_work_program_id);
            ErkapAccess::assertWorkProgramAccess($request->integer('erkap_work_program_id'));

            $targetWorkProgramId = $request->filled('erkap_work_program_id')
                ? $request->integer('erkap_work_program_id')
                : $routineCost->erkap_work_program_id;

            RKAPLifecycleService::assertNotLocked(
                RKAPLifecycleService::resolveForWorkProgram($targetWorkProgramId),
                'Biaya rutin'
            );

            $data = $request->validated();
            $data['chart_of_account_id'] = $this->resolveChartOfAccountId($data, $routineCost);
            $data['is_kumulatif'] = $request->boolean('is_kumulatif');

            $this->assertCentralizedCostInput($data, $targetWorkProgramId, $routineCost);

            if (! $this->validateTotal($data, $data['is_kumulatif'])) {
                return redirect()->route('erkap.routine-costs.edit', $routineCost->id)
                    ->withInput()
                    ->withErrors(['total' => $data['is_kumulatif']
                        ? 'Total harus sama dengan qty × harga satuan.'
                        : 'Total harus sama dengan qty × harga satuan dan jumlah seluruh bulanan.']);
            }

            $data['total'] = $data['is_kumulatif']
                ? (float) $data['qty'] * (float) $data['unit_price']
                : $this->sumMonths($data);

            $routineCost->update($data);

            $this->rebuildZbb($targetWorkProgramId);

            return redirect()->route('erkap.routine-costs.index')
                ->with('success', 'Biaya rutin berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.routine-costs.edit', $routineCost->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(RoutineCost $routineCost)
    {
        try {
            ErkapAccess::assertWorkProgramAccess($routineCost->erkap_work_program_id);

            $routineCost->delete();

            return redirect()->route('erkap.routine-costs.index')
                ->with('success', 'Biaya rutin berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.routine-costs.index')->with('error', $err->getMessage());
        }
    }

    public function submit(RoutineCost $routineCost)
    {
        try {
            ErkapAccess::assertWorkProgramAccess($routineCost->erkap_work_program_id);

            ApprovalService::submit($routineCost);

            return redirect()->route('erkap.routine-costs.index')
                ->with('success', 'Biaya rutin berhasil diajukan untuk persetujuan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.routine-costs.index')->with('error', $err->getMessage());
        }
    }

    public function submitBatch()
    {
        try {
            $routineCosts = RoutineCost::query()
                ->whereIn('erkap_work_program_id', ErkapAccess::workProgramIds())
                ->whereIn('status', ['draft', 'rejected'])
                ->get();

            if ($routineCosts->isEmpty()) {
                return redirect()->route('erkap.routine-costs.index')
                    ->with('error', 'Tidak ada biaya rutin yang dapat diajukan untuk persetujuan.');
            }

            $results = ApprovalService::submitBatch($routineCosts);

            $message = "{$results['submitted']} biaya rutin berhasil diajukan untuk persetujuan.";

            if ($results['skipped'] > 0) {
                $message .= " {$results['skipped']} dilewati (sudah dalam proses/disetujui).";
            }

            if ($results['failed'] > 0) {
                $message .= " {$results['failed']} gagal diajukan.";
            }

            if ($results['failed'] > 0 && $results['errors']) {
                $message .= ' ('.$results['errors'][0].')';
            }

            return redirect()->route('erkap.routine-costs.index')
                ->with($results['failed'] > 0 ? 'error' : 'success', $message);
        } catch (Exception $err) {
            return redirect()->route('erkap.routine-costs.index')->with('error', $err->getMessage());
        }
    }

    public function consolidate(Request $request)
    {
        $pageName = 'Konsolidasi Biaya Rutin (OPEX)';
        $rkapList = RKAP::orderByDesc('year')->get();

        $divisionQuery = Division::query()
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->where('id', ErkapAccess::divisionId());
            });
        $divisions = $divisionQuery->get();

        $query = RoutineCost::with(['costElement', 'costCenter.coordinatingDivision', 'workProgram.riskIdentification.departmentTarget.division']);

        if ($request->filled('division_id')) {
            $query->whereHas('workProgram.riskIdentification.departmentTarget', function ($q) use ($request) {
                $q->where('division_id', $request->integer('division_id'));
            });
        } elseif (ErkapAccess::isDivisionScoped() && ErkapAccess::divisionId()) {
            $query->whereHas('workProgram.riskIdentification.departmentTarget', function ($q) {
                $q->where('division_id', ErkapAccess::divisionId());
            });
        }

        $routineCosts = $query->get();

        $consolidated = $routineCosts
            ->groupBy('erkap_cost_element_id')
            ->map(function (Collection $items, $elementId) {
                $monthly = [];
                foreach (self::MONTHS as $month) {
                    $monthly[$month] = $items->sum($month);
                }

                $costCenter = $items->first()?->costCenter;

                return [
                    'erkap_cost_element_id' => $elementId,
                    'cost_element' => $items->first()->costElement,
                    'total_qty' => $items->sum('qty'),
                    'total_cost' => $items->sum('total'),
                    'monthly' => $monthly,
                    'is_centralized' => $costCenter?->isCentralized() ?? false,
                    'coordinator' => $costCenter?->coordinatingDivision?->name,
                ];
            })
            ->values();

        $centralizedGroups = CentralizedCostService::groupCentralized($routineCosts);

        $grandTotal = $consolidated->sum('total_cost');
        $totalByMonth = [];
        foreach (self::MONTHS as $month) {
            $totalByMonth[$month] = $routineCosts->sum($month);
        }

        return view(
            'erkap.routine-cost.consolidate',
            compact('pageName', 'consolidated', 'grandTotal', 'totalByMonth', 'rkapList', 'divisions', 'centralizedGroups')
        );
    }

    private function assertCentralizedCostInput(array $data, ?int $workProgramId, ?RoutineCost $routineCost = null): void
    {
        $costCenterId = $data['cost_center_id'] ?? $routineCost?->cost_center_id;

        $costCenter = $costCenterId ? CostCenter::find($costCenterId) : null;

        if (! $costCenter) {
            return;
        }

        $workProgram = WorkProgram::with('riskIdentification.departmentTarget')->find($workProgramId);
        $divisionId = $workProgram?->riskIdentification?->departmentTarget?->division_id;

        CentralizedCostService::assertCanInput($costCenter, $divisionId);
    }

    private function resolveChartOfAccountId(array $data, ?RoutineCost $routineCost = null): ?int
    {
        if (filled($data['chart_of_account_id'] ?? null)) {
            return (int) $data['chart_of_account_id'];
        }

        if ($routineCost?->chart_of_account_id) {
            return (int) $routineCost->chart_of_account_id;
        }

        return CostElement::find($data['erkap_cost_element_id'])?->coaSuggestion()?->id;
    }

    private function groupedCostCenters(Collection $costElements): array
    {
        $costCenters = CostCenter::query()
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->where('division_id', ErkapAccess::divisionId());
            })
            ->get()
            ->map(function (CostCenter $costCenter) use ($costElements) {
                $costCenter->cost_element_id = $costElements
                    ->firstWhere('code', $costCenter->costElementCode())?->id;

                return $costCenter;
            });

        return [
            $costCenters->filter(fn (CostCenter $costCenter) => $costCenter->isSwakelola())->values(),
            $costCenters->reject(fn (CostCenter $costCenter) => $costCenter->isSwakelola())->values(),
        ];
    }

    private function validateTotal(array $data, bool $isKumulatif): bool
    {
        $expectedTotal = (float) $data['qty'] * (float) $data['unit_price'];
        $total = (float) ($data['total'] ?? 0);

        if ($isKumulatif) {
            return abs($expectedTotal - $total) <= 0.01;
        }

        $monthlyTotal = $this->sumMonths($data);

        return abs($expectedTotal - $total) <= 0.01
            && abs($monthlyTotal - $total) <= 0.01;
    }

    private function sumMonths(array $data): float
    {
        return array_sum(
            array_map(fn ($month) => (float) ($data[$month] ?? 0), self::MONTHS)
        );
    }

    private function subtotalMap(string $groupBy): array
    {
        return RoutineCost::query()
            ->whereIn('erkap_work_program_id', ErkapAccess::workProgramIds())
            ->select($groupBy, DB::raw('SUM(total) as subtotal'))
            ->groupBy($groupBy)
            ->get()
            ->pluck('subtotal', $groupBy)
            ->map(fn ($value) => (float) $value)
            ->all();
    }

    private function subtotalByCostCenterMap(): array
    {
        return RoutineCost::query()
            ->whereIn('erkap_work_program_id', ErkapAccess::workProgramIds())
            ->select('cost_center_id', DB::raw('SUM(total) as subtotal'))
            ->groupBy('cost_center_id')
            ->get()
            ->pluck('subtotal', 'cost_center_id')
            ->map(fn ($value) => (float) $value)
            ->all();
    }

    public function budgetPreviewByProgram(): array
    {
        $workProgramIds = ErkapAccess::workProgramIds();

        $costsByProgram = RoutineCost::query()
            ->whereIn('erkap_work_program_id', $workProgramIds)
            ->select('id', 'erkap_work_program_id', 'total')
            ->get()
            ->groupBy('erkap_work_program_id');

        $routineCostIds = $costsByProgram->flatten()->pluck('id')->all();

        $realizedByCost = BudgetRealization::query()
            ->whereIn('erkap_routine_cost_id', $routineCostIds)
            ->select('erkap_routine_cost_id', DB::raw('SUM(realized) as realized'))
            ->groupBy('erkap_routine_cost_id')
            ->get()
            ->pluck('realized', 'erkap_routine_cost_id')
            ->map(fn ($value) => (float) $value)
            ->all();

        $preview = [];

        foreach ($costsByProgram as $programId => $rows) {
            $budget = (float) $rows->sum('total');
            $realized = collect($rows)->sum(fn ($row) => $realizedByCost[$row->id] ?? 0);
            $variance = $budget - $realized;

            $preview[$programId] = [
                'budget' => $budget,
                'realized' => $realized,
                'variance' => $variance,
                'variance_pct' => $budget > 0 ? round(($variance / $budget) * 100, 2) : 0,
            ];
        }

        return $preview;
    }

    private function rebuildZbb(?int $workProgramId): void
    {
        $rkap = RKAPLifecycleService::resolveForWorkProgram($workProgramId);

        if ($rkap) {
            ZBBReviewService::buildReviews($rkap);
        }
    }
}