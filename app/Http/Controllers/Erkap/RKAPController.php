<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRKAPRequest;
use App\Http\Requests\UpdateRKAPRequest;
use App\Models\Erkap\RKAP;
use Exception;

class RKAPController extends Controller
{
    public function index()
    {
        $pageName = 'Periode RKAP';
        $rkaps = RKAP::orderByDesc('year')->paginate(10);

        return view('erkap.rkap.index', compact('pageName', 'rkaps'));
    }

    public function create()
    {
        $pageName = 'Buat Periode RKAP';

        return view('erkap.rkap.create', compact('pageName'));
    }

    public function store(StoreRKAPRequest $request)
    {
        try {
            RKAP::create($request->validated());

            return redirect()->route('erkap.rkap.index')
                ->with('success', 'Periode RKAP baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.rkap.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(RKAP $rkap)
    {
        $pageName = 'Edit Periode RKAP';

        return view('erkap.rkap.edit', compact('pageName', 'rkap'));
    }

    public function update(UpdateRKAPRequest $request, RKAP $rkap)
    {
        try {
            $rkap->update($request->validated());

            return redirect()->route('erkap.rkap.index')
                ->with('success', 'Periode RKAP berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.rkap.edit', $rkap->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(RKAP $rkap)
    {
        try {
            if ($rkap->companyTargets()->exists()) {
                return redirect()->route('erkap.rkap.index')
                    ->with('error', 'Periode RKAP tidak dapat dihapus karena masih memiliki company target!');
            }

            $rkap->delete();

            return redirect()->route('erkap.rkap.index')
                ->with('success', 'Periode RKAP berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.rkap.index')->with('error', $err->getMessage());
        }
    }
}