<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiskAssessmentMonthlyRequest;
use App\Models\Erkap\RiskAssessmentMonthly;
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

        $assessments = RiskAssessmentMonthly::with(['riskIdentification.departmentTarget.division'])
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

        return view('erkap.risk-assessments-monthly.create', compact('pageName', 'riskIdentifications', 'monthLabels'));
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