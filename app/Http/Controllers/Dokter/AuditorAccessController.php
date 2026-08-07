<?php

namespace App\Http\Controllers\Dokter;

use App\Http\Controllers\Controller;
use App\Models\AuditorAccessLink;
use Illuminate\Http\Request;

class AuditorAccessController extends Controller
{
    public function index()
    {
        $pageName = 'Akses Auditor';
        $links = AuditorAccessLink::latest()->get();

        return view('dokter.auditor-access.index', compact('pageName', 'links'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'allowed_years' => 'required|array|min:1',
            'allowed_years.*' => 'integer|min:2000|max:2100',
        ]);

        $employeeId = auth()->user()->employee_id;

        AuditorAccessLink::create([
            'name' => $request->name,
            'token' => AuditorAccessLink::generateToken(),
            'description' => $request->description,
            'allowed_years' => $request->allowed_years,
            'is_active' => true,
            'created_by' => $employeeId,
        ]);

        return redirect()->route('dokter.auditor-access.index')
            ->with('success', 'Link akses auditor berhasil dibuat.');
    }

    public function show(AuditorAccessLink $auditorAccessLink)
    {
        $pageName = 'Detail Akses Auditor';
        $fullUrl = url("/auditor/{$auditorAccessLink->token}");

        return view('dokter.auditor-access.show', compact('pageName', 'auditorAccessLink', 'fullUrl'));
    }

    public function update(Request $request, AuditorAccessLink $auditorAccessLink)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'allowed_years' => 'required|array|min:1',
            'allowed_years.*' => 'integer|min:2000|max:2100',
        ]);

        $auditorAccessLink->update([
            'name' => $request->name,
            'description' => $request->description,
            'allowed_years' => $request->allowed_years,
        ]);

        return redirect()->route('dokter.auditor-access.index')
            ->with('success', 'Link akses auditor berhasil diperbarui.');
    }

    public function destroy(AuditorAccessLink $auditorAccessLink)
    {
        $auditorAccessLink->delete();

        return redirect()->route('dokter.auditor-access.index')
            ->with('success', 'Link akses auditor berhasil dihapus.');
    }

    public function toggle(AuditorAccessLink $auditorAccessLink)
    {
        $auditorAccessLink->update([
            'is_active' => !$auditorAccessLink->is_active,
        ]);

        $status = $auditorAccessLink->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('dokter.auditor-access.index')
            ->with('success', "Link akses auditor berhasil {$status}.");
    }
}
