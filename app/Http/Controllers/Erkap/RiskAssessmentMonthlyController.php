<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiskAssessmentMonthlyRequest;
use App\Http\Requests\UpdateRiskAssessmentMonthlyRequest;
use App\Models\Erkap\RiskAppetite;
use App\Models\Erkap\RiskAssessmentMonthly;
use App\Models\Erkap\RiskBusinessProcess;
use App\Models\Erkap\RiskIdentification;
use App\Services\ErkapAccess;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RiskAssessmentMonthlyController extends Controller
{
    protected $monthLabels = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    public function index(Request $request)
    {
        $pageName = 'Risk Assessment Bulanan';
        $year = $request->integer('year', now()->year);

        $assessments = RiskAssessmentMonthly::with(['riskIdentification.departmentTarget.division', 'riskAppetite', 'businessProcesses'])
            ->where('year', $year)
            ->when($request->filled('month'), fn ($q) => $q->where('month', $request->integer('month')))
            ->latest()
            ->paginate(10);

        $monthLabels = $this->monthLabels;

        return view('erkap.risk-assessments-monthly.index', compact('pageName', 'assessments', 'monthLabels', 'year'));
    }

    public function create()
    {
        $pageName = 'Input Risk Assessment Bulanan';
        $riskIdentifications = RiskIdentification::with('departmentTarget.division')
            ->whereIn('id', ErkapAccess::riskIdentificationIds())
            ->orderBy('id')
            ->get();
        $monthLabels = $this->monthLabels;
        $riskAppetites = RiskAppetite::orderBy('name')->get();

        return view('erkap.risk-assessments-monthly.create', compact('pageName', 'riskIdentifications', 'monthLabels', 'riskAppetites'));
    }

    public function store(StoreRiskAssessmentMonthlyRequest $request)
    {
        try {
            ErkapAccess::assertRiskIdentificationAccess($request->integer('erkap_risk_identification_id'));

            $data = $request->validated();
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $assessment = RiskAssessmentMonthly::updateOrCreate(
                [
                    'erkap_risk_identification_id' => $data['erkap_risk_identification_id'],
                    'month' => $data['month'],
                    'year' => $data['year'],
                ],
                $data
            );
            $assessment->calculateScores();
            $assessment->markOverdueIfDue();
            $this->syncBusinessProcesses($assessment, $request);

            return redirect()->route('erkap.risk-assessments-monthly.index')
                ->with('success', 'Risk assessment bulanan berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-assessments-monthly.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(RiskAssessmentMonthly $riskAssessmentMonthly)
    {
        $riskAssessmentMonthly->loadMissing([
            'riskIdentification.departmentTarget.division',
            'riskIdentification.departmentTarget.companyTarget.rkap',
            'businessProcesses',
        ]);

        $pageName = 'Edit Risk Assessment Bulanan';
        $riskIdentifications = RiskIdentification::with('departmentTarget.division')
            ->whereIn('id', ErkapAccess::riskIdentificationIds())
            ->orderBy('id')
            ->get();
        $monthLabels = $this->monthLabels;
        $riskAppetites = RiskAppetite::orderBy('name')->get();

        return view(
            'erkap.risk-assessments-monthly.edit',
            compact('pageName', 'riskAssessmentMonthly', 'riskIdentifications', 'monthLabels', 'riskAppetites')
        );
    }

    public function update(UpdateRiskAssessmentMonthlyRequest $request, RiskAssessmentMonthly $riskAssessmentMonthly)
    {
        try {
            ErkapAccess::assertRiskIdentificationAccess($request->integer('erkap_risk_identification_id'));

            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            $riskAssessmentMonthly->update($data);
            $riskAssessmentMonthly->calculateScores();
            $riskAssessmentMonthly->markOverdueIfDue();
            $this->syncBusinessProcesses($riskAssessmentMonthly, $request);

            return redirect()->route('erkap.risk-assessments-monthly.index')
                ->with('success', 'Risk assessment bulanan berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-assessments-monthly.edit', $riskAssessmentMonthly->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    protected function syncBusinessProcesses(RiskAssessmentMonthly $assessment, Request $request): void
    {
        $items = $request->input('business_processes', []);

        if (! is_array($items) || empty($items)) {
            $assessment->businessProcesses()->delete();

            return;
        }

        $processes = [];
        foreach ($items as $item) {
            $item = is_array($item) ? $item : [];
            $processName = trim((string) ($item['process_name'] ?? ''));

            if ($processName === '') {
                continue;
            }

            $processes[] = [
                'risk_assessment_monthly_id' => $assessment->id,
                'process_name' => $processName,
                'description' => trim((string) ($item['description'] ?? '')) ?: null,
                'owner' => trim((string) ($item['owner'] ?? '')) ?: null,
                'risk_level' => in_array($item['risk_level'] ?? null, ['low', 'medium', 'high', 'critical'], true)
                    ? $item['risk_level']
                    : 'medium',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $assessment->businessProcesses()->delete();

        if (! empty($processes)) {
            RiskBusinessProcess::insert($processes);
        }
    }

    public function destroy(RiskAssessmentMonthly $riskAssessmentMonthly)
    {
        try {
            $riskAssessmentMonthly->delete();

            return redirect()->route('erkap.risk-assessments-monthly.index')
                ->with('success', 'Risk assessment bulanan berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-assessments-monthly.index')->with('error', $err->getMessage());
        }
    }
}