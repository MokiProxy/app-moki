<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProgramRealizationRequest;
use App\Models\Erkap\ProgramRealization;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\WorkProgram;
use App\Services\ErkapAccess;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProgramRealizationController extends Controller
{
    protected $monthLabels = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    public function index(Request $request)
    {
        $pageName = 'Realisasi Program Kerja';
        $rkaps = RKAP::orderByDesc('year')->get();
        $selectedRkap = RKAP::find($request->integer('rkap_id')) ?? $rkaps->first();
        $year = $selectedRkap ? (int) $selectedRkap->year : now()->year;

        $realizations = ProgramRealization::with(['workProgram.riskIdentification.departmentTarget.division'])
            ->where('year', $year)
            ->when($request->filled('month'), fn ($q) => $q->where('month', $request->integer('month')))
            ->latest()
            ->paginate(10);

        $monthLabels = $this->monthLabels;

        return view('erkap.program-realizations.index', compact('pageName', 'rkaps', 'realizations', 'monthLabels', 'year', 'selectedRkap'));
    }

    public function create()
    {
        $pageName = 'Input Realisasi Program Kerja';
        $rkaps = RKAP::orderByDesc('year')->get();
        $workPrograms = WorkProgram::with('riskIdentification.departmentTarget.division')
            ->whereIn('id', ErkapAccess::workProgramIds())
            ->orderBy('id')
            ->get();
        $monthLabels = $this->monthLabels;

        return view('erkap.program-realizations.create', compact('pageName', 'rkaps', 'workPrograms', 'monthLabels'));
    }

    public function store(StoreProgramRealizationRequest $request)
    {
        try {
            $data = $request->validated();
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $realization = ProgramRealization::updateOrCreate(
                [
                    'erkap_work_program_id' => $data['erkap_work_program_id'],
                    'month' => $data['month'],
                    'year' => $data['year'],
                ],
                $data
            );
            $realization->calculatePercentComplete();

            return redirect()->route('erkap.program-realizations.index')
                ->with('success', 'Realisasi program kerja berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.program-realizations.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(ProgramRealization $programRealization)
    {
        try {
            $programRealization->delete();

            return redirect()->route('erkap.program-realizations.index')
                ->with('success', 'Realisasi program kerja berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.program-realizations.index')->with('error', $err->getMessage());
        }
    }
}