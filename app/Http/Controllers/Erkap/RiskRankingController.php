<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiskRankingRequest;
use App\Http\Requests\UpdateRiskRankingRequest;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskRanking;
use App\Services\ErkapAccess;
use App\Services\ErkapEvaluationLock;
use Exception;

class RiskRankingController extends Controller
{
    public function index()
    {
        $pageName = 'Peringkat Risiko';
        $riskRankings = RiskRanking::with('riskIdentification')
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->whereIn('erkap_risk_identification_id', ErkapAccess::riskIdentificationIds());
            })
            ->paginate(10);

        return view('erkap.risk-ranking.index', compact('pageName', 'riskRankings'));
    }

    public function create()
    {
        $pageName = 'Buat Peringkat Risiko';
        $riskIdentifications = RiskIdentification::whereIn('id', ErkapAccess::riskIdentificationIds())->get();

        return view('erkap.risk-ranking.create', compact('pageName', 'riskIdentifications'));
    }

    public function store(StoreRiskRankingRequest $request)
    {
        try {
            ErkapAccess::assertRiskIdentificationAccess($request->integer('erkap_risk_identification_id'));
            ErkapEvaluationLock::assertRiskEditable(RiskIdentification::findOrFail($request->integer('erkap_risk_identification_id')));

            RiskRanking::create($request->validated());

            return redirect()->route('erkap.risk-rankings.index')
                ->with('success', 'Peringkat risiko baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-rankings.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(RiskRanking $riskRanking)
    {
        ErkapAccess::assertRiskIdentificationAccess($riskRanking->erkap_risk_identification_id);

        try {
            ErkapEvaluationLock::assertRiskEditable(RiskIdentification::find($riskRanking->erkap_risk_identification_id));
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-rankings.index')
                ->with('error', $err->getMessage());
        }

        $pageName = 'Edit Peringkat Risiko';
        $riskIdentifications = RiskIdentification::whereIn('id', ErkapAccess::riskIdentificationIds())->get();

        return view('erkap.risk-ranking.edit', compact('pageName', 'riskRanking', 'riskIdentifications'));
    }

    public function update(UpdateRiskRankingRequest $request, RiskRanking $riskRanking)
    {
        try {
            ErkapAccess::assertRiskIdentificationAccess($riskRanking->erkap_risk_identification_id);
            ErkapAccess::assertRiskIdentificationAccess($request->integer('erkap_risk_identification_id'));
            ErkapEvaluationLock::assertRiskEditable(RiskIdentification::find($riskRanking->erkap_risk_identification_id));
            ErkapEvaluationLock::assertRiskEditable(RiskIdentification::find($request->integer('erkap_risk_identification_id')));

            $riskRanking->update($request->validated());

            return redirect()->route('erkap.risk-rankings.index')
                ->with('success', 'Peringkat risiko berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-rankings.edit', $riskRanking->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(RiskRanking $riskRanking)
    {
        try {
            ErkapAccess::assertRiskIdentificationAccess($riskRanking->erkap_risk_identification_id);
            ErkapEvaluationLock::assertRiskEditable(RiskIdentification::find($riskRanking->erkap_risk_identification_id));

            $riskRanking->delete();

            return redirect()->route('erkap.risk-rankings.index')
                ->with('success', 'Peringkat risiko berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-rankings.index')->with('error', $err->getMessage());
        }
    }
}
