<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiskRankingRequest;
use App\Http\Requests\UpdateRiskRankingRequest;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskRanking;
use Exception;

class RiskRankingController extends Controller
{
    public function index()
    {
        $pageName = 'Peringkat Risiko';
        $riskRankings = RiskRanking::with('riskIdentification')->paginate(10);

        return view('erkap.risk-ranking.index', compact('pageName', 'riskRankings'));
    }

    public function create()
    {
        $pageName = 'Buat Peringkat Risiko';
        $riskIdentifications = RiskIdentification::all();

        return view('erkap.risk-ranking.create', compact('pageName', 'riskIdentifications'));
    }

    public function store(StoreRiskRankingRequest $request)
    {
        try {
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
        $pageName = 'Edit Peringkat Risiko';
        $riskIdentifications = RiskIdentification::all();

        return view('erkap.risk-ranking.edit', compact('pageName', 'riskRanking', 'riskIdentifications'));
    }

    public function update(UpdateRiskRankingRequest $request, RiskRanking $riskRanking)
    {
        try {
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
            $riskRanking->delete();

            return redirect()->route('erkap.risk-rankings.index')
                ->with('success', 'Peringkat risiko berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.risk-rankings.index')->with('error', $err->getMessage());
        }
    }
}