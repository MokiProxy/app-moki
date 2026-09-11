<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoutineCostRequest;
use App\Http\Requests\UpdateRoutineCostRequest;
use App\Models\Erkap\CostElement;
use App\Models\Erkap\RoutineCost;
use App\Models\Erkap\WorkProgram;
use Exception;

class RoutineCostController extends Controller
{
    public function index()
    {
        $pageName = 'Biaya Rutin';
        $routineCosts = RoutineCost::with(['workProgram', 'costElement'])->paginate(10);

        return view('erkap.routine-cost.index', compact('pageName', 'routineCosts'));
    }

    public function create()
    {
        $pageName = 'Buat Biaya Rutin';
        $workPrograms = WorkProgram::all();
        $costElements = CostElement::all();

        return view('erkap.routine-cost.create', compact('pageName', 'workPrograms', 'costElements'));
    }

    public function store(StoreRoutineCostRequest $request)
    {
        try {
            $data = $request->validated();
            $data['total'] = $this->calcTotal($data);

            RoutineCost::create($data);

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
        $pageName = 'Edit Biaya Rutin';
        $workPrograms = WorkProgram::all();
        $costElements = CostElement::all();

        return view('erkap.routine-cost.edit', compact('pageName', 'routineCost', 'workPrograms', 'costElements'));
    }

    public function update(UpdateRoutineCostRequest $request, RoutineCost $routineCost)
    {
        try {
            $data = $request->validated();
            $data['total'] = $this->calcTotal($data);

            $routineCost->update($data);

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
            $routineCost->delete();

            return redirect()->route('erkap.routine-costs.index')
                ->with('success', 'Biaya rutin berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.routine-costs.index')->with('error', $err->getMessage());
        }
    }

    private function calcTotal(array $data): float
    {
        $months = [
            'jan_cost', 'feb_cost', 'mar_cost', 'apr_cost', 'may_cost', 'jun_cost',
            'jul_cost', 'aug_cost', 'sep_cost', 'oct_cost', 'nov_cost', 'des_cost',
        ];

        return array_sum(array_map(fn ($month) => (float) ($data[$month] ?? 0), $months));
    }
}
