<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkProgramRequest;
use App\Http\Requests\UpdateWorkProgramRequest;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\WorkProgram;
use Exception;

class WorkProgramController extends Controller
{
    public function index()
    {
        $pageName = 'Program Kerja';
        $workPrograms = WorkProgram::with('riskIdentification')->paginate(10);

        return view('erkap.work-program.index', compact('pageName', 'workPrograms'));
    }

    public function create()
    {
        $pageName = 'Buat Program Kerja';
        $riskIdentifications = RiskIdentification::all();

        return view('erkap.work-program.create', compact('pageName', 'riskIdentifications'));
    }

    public function store(StoreWorkProgramRequest $request)
    {
        try {
            WorkProgram::create($request->validated());

            return redirect()->route('erkap.work-programs.index')
                ->with('success', 'Program kerja baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.work-programs.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(WorkProgram $workProgram)
    {
        $pageName = 'Edit Program Kerja';
        $riskIdentifications = RiskIdentification::all();

        return view('erkap.work-program.edit', compact('pageName', 'workProgram', 'riskIdentifications'));
    }

    public function update(UpdateWorkProgramRequest $request, WorkProgram $workProgram)
    {
        try {
            $workProgram->update($request->validated());

            return redirect()->route('erkap.work-programs.index')
                ->with('success', 'Program kerja berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.work-programs.edit', $workProgram->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(WorkProgram $workProgram)
    {
        try {
            $workProgram->delete();

            return redirect()->route('erkap.work-programs.index')
                ->with('success', 'Program kerja berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.work-programs.index')->with('error', $err->getMessage());
        }
    }
}