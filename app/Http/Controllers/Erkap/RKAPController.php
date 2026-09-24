<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\Erkap\StoreRKAPDirectionRequest;
use App\Http\Requests\Erkap\StoreRKAPKickoffRequest;
use App\Http\Requests\Erkap\UpdateRKAPBmiRequest;
use App\Http\Requests\StoreRKAPRequest;
use App\Http\Requests\UpdateRKAPRequest;
use App\Models\Division;
use App\Models\Erkap\RKAP;
use App\Services\ApprovalService;
use App\Services\Erkap\RKAPLifecycleService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RKAPController extends Controller
{
    public function index()
    {
        $pageName = 'Periode RKAP';
        $rkaps = RKAP::withCount('kickoffAttendees')->orderByDesc('year')->paginate(10);

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

    public function show(RKAP $rkap)
    {
        $pageName = 'Detail Periode RKAP '.$rkap->year;

        $rkap->loadMissing([
            'companyTargets',
            'kickoffAttendees.division',
            'approvals.approver',
            'audits',
        ]);

        $divisions = Division::orderBy('name')->get();
        $phases = collect(RKAP::PHASES)->mapWithKeys(function (string $phase) {
            return [$phase => RKAP::PHASE_LABELS[$phase] ?? ucfirst($phase)];
        })->all();

        $currentIndex = array_search($rkap->phase, RKAP::PHASES, true);
        $canAdvance = auth()->user()->can('erkap.rkap.edit') && $rkap->nextPhase() !== null;

        return view('erkap.rkap.show', compact('pageName', 'rkap', 'divisions', 'phases', 'currentIndex', 'canAdvance'));
    }

    public function advance(RKAP $rkap)
    {
        try {
            RKAPLifecycleService::advance($rkap);

            return redirect()->route('erkap.rkap.show', $rkap->id)
                ->with('success', 'Fase RKAP bergerak ke "'.$rkap->phaseLabel().'".');
        } catch (Exception $err) {
            return redirect()->route('erkap.rkap.show', $rkap->id)
                ->with('error', $err->getMessage());
        }
    }

    public function kickoff(StoreRKAPKickoffRequest $request, RKAP $rkap)
    {
        try {
            $validated = $request->validated();

            $rkap->update([
                'kickoff_date' => $validated['kickoff_date'] ?? null,
                'kickoff_notes' => $validated['kickoff_notes'] ?? null,
            ]);

            if (! empty($validated['attendees'])) {
                $rkap->kickoffAttendees()->delete();

                foreach ($validated['attendees'] as $attendee) {
                    $rkap->kickoffAttendees()->create([
                        'name' => $attendee['name'],
                        'division_id' => $attendee['division_id'] ?? null,
                        'attended' => (bool) ($attendee['attended'] ?? false),
                    ]);
                }
            }

            return redirect()->route('erkap.rkap.show', $rkap->id)
                ->with('success', 'Jadwal dan peserta kick-off/sosialisasi berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.rkap.show', $rkap->id)
                ->with('error', $err->getMessage());
        }
    }

    public function direction(StoreRKAPDirectionRequest $request, RKAP $rkap)
    {
        try {
            $validated = $request->validated();

            $data = ['direction_notes' => $validated['direction_notes'] ?? null];

            if ($request->hasFile('direction_file')) {
                if ($rkap->direction_file_path) {
                    Storage::disk('public')->delete($rkap->direction_file_path);
                }

                $data['direction_file_path'] = $request
                    ->file('direction_file')
                    ->store('rkap-directions', 'public');
            }

            $rkap->update($data);

            return redirect()->route('erkap.rkap.show', $rkap->id)
                ->with('success', 'Arahan direksi / memo holding berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.rkap.show', $rkap->id)
                ->with('error', $err->getMessage());
        }
    }

    public function bmi(UpdateRKAPBmiRequest $request, RKAP $rkap)
    {
        try {
            RKAPLifecycleService::markBmiAligned($rkap, auth()->user(), $request->validated());

            return redirect()->route('erkap.rkap.show', $rkap->id)
                ->with('success', 'Status alignment PT BMI berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.rkap.show', $rkap->id)
                ->with('error', $err->getMessage());
        }
    }

    public function distribute(Request $request, RKAP $rkap)
    {
        try {
            RKAPLifecycleService::distribute($rkap, auth()->user());

            return redirect()->route('erkap.rkap.show', $rkap->id)
                ->with('success', 'Dokumen RKAP ditandai telah didistribusikan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.rkap.show', $rkap->id)
                ->with('error', $err->getMessage());
        }
    }

    public function resetPhase(Request $request, RKAP $rkap)
    {
        try {
            RKAPLifecycleService::resetPhase($rkap);

            return redirect()->route('erkap.rkap.show', $rkap->id)
                ->with('success', 'Fase lifecycle RKAP direset ke Inisiasi.');
        } catch (Exception $err) {
            return redirect()->route('erkap.rkap.show', $rkap->id)
                ->with('error', $err->getMessage());
        }
    }

    public function downloadDirection(RKAP $rkap)
    {
        if (! $rkap->direction_file_path) {
            return redirect()->route('erkap.rkap.show', $rkap->id)
                ->with('error', 'Lampiran arahan direksi tidak tersedia.');
        }

        return Storage::disk('public')->download($rkap->direction_file_path);
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
            if ($rkap->isLockedForInput()) {
                return redirect()->route('erkap.rkap.index')
                    ->with('error', 'Periode RKAP tidak dapat dihapus karena sudah memasuki fase "'.$rkap->phaseLabel().'".');
            }

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

    public function submit(RKAP $rkap)
    {
        try {
            ApprovalService::submit($rkap);

            return redirect()->route('erkap.rkap.index')
                ->with('success', 'Periode RKAP berhasil diajukan untuk persetujuan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.rkap.index')
                ->with('error', $err->getMessage());
        }
    }
}