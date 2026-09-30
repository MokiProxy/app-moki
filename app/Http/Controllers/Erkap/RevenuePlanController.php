<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRevenuePlanRequest;
use App\Http\Requests\UpdateRevenuePlanRequest;
use App\Models\Division;
use App\Models\Erkap\RevenuePlan;
use App\Models\Erkap\RKAP;
use App\Services\Erkap\RKAPLifecycleService;
use App\Services\Erkap\ZBBReviewService;
use App\Services\ErkapAccess;
use App\Support\ErrorMessage;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RevenuePlanController extends Controller
{
    public function index()
    {
        $pageName = 'Rencana Pendapatan';
        $lockedRkaps = RKAP::lockedForInput()->orderByDesc('year')->get();
        $revenuePlans = RevenuePlan::with(['rkap', 'division', 'chartOfAccount'])
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->where('division_id', ErkapAccess::divisionId());
            })
            ->latest()
            ->paginate(10);

        return view('erkap.revenue-plan.index', compact('pageName', 'revenuePlans', 'lockedRkaps'));
    }

    public function create()
    {
        $pageName = 'Buat Rencana Pendapatan';
        $rkaps = RKAP::orderByDesc('year')->get();
        $divisions = $this->availableDivisions();

        return view('erkap.revenue-plan.create', compact('pageName', 'rkaps', 'divisions'));
    }

    public function store(StoreRevenuePlanRequest $request)
    {
        try {
            ErkapAccess::assertDivisionAccess($request->integer('division_id'));
            RKAPLifecycleService::assertNotLocked(
                RKAP::find($request->integer('erkap_rkap_id')),
                'Rencana pendapatan'
            );

            $data = $request->validated();
            $data['total'] = $this->sumMonths($data);
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            RevenuePlan::create($data);

            $this->rebuildZbb(RKAP::find($request->integer('erkap_rkap_id')));

            return redirect()->route('erkap.revenue-plans.index')
                ->with('success', 'Rencana pendapatan baru berhasil disimpan!');
        } catch (ValidationException $err) {
            // `back()` bergantung pada header Referer, jadi pada request tanpa
            // asal (API, test) ia melompat ke root. Redirect eksplisit ke form
            // menjaga pesan error tetap terlihat di halaman yang benar.
            return redirect()->route('erkap.revenue-plans.create')
                ->withInput()
                ->with('error', ErrorMessage::from($err))
                ->withErrors($err->errors());
        } catch (Exception $err) {
            return redirect()->route('erkap.revenue-plans.create')
                ->withInput()
                ->with('error', ErrorMessage::from($err))
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(RevenuePlan $revenuePlan)
    {
        ErkapAccess::assertDivisionAccess($revenuePlan->division_id);

        $pageName = 'Edit Rencana Pendapatan';
        $rkaps = RKAP::orderByDesc('year')->get();
        $divisions = $this->availableDivisions();

        return view('erkap.revenue-plan.edit', compact('pageName', 'revenuePlan', 'rkaps', 'divisions'));
    }

    public function update(UpdateRevenuePlanRequest $request, RevenuePlan $revenuePlan)
    {
        try {
            ErkapAccess::assertDivisionAccess($revenuePlan->division_id);
            ErkapAccess::assertDivisionAccess($request->integer('division_id'));

            $targetRkapId = $request->filled('erkap_rkap_id')
                ? $request->integer('erkap_rkap_id')
                : $revenuePlan->erkap_rkap_id;

            RKAPLifecycleService::assertNotLocked(
                RKAP::find($targetRkapId),
                'Rencana pendapatan'
            );

            $data = $request->validated();
            $data['total'] = $this->sumMonths($data);
            $data['updated_by'] = Auth::id();

            $revenuePlan->update($data);

            $this->rebuildZbb(RKAP::find($targetRkapId));

            return redirect()->route('erkap.revenue-plans.index')
                ->with('success', 'Rencana pendapatan berhasil diperbarui!');
        } catch (ValidationException $err) {
            return redirect()->route('erkap.revenue-plans.edit', $revenuePlan->id)
                ->withInput()
                ->with('error', ErrorMessage::from($err))
                ->withErrors($err->errors());
        } catch (Exception $err) {
            return redirect()->route('erkap.revenue-plans.edit', $revenuePlan->id)
                ->withInput()
                ->with('error', ErrorMessage::from($err))
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(RevenuePlan $revenuePlan)
    {
        try {
            ErkapAccess::assertDivisionAccess($revenuePlan->division_id);

            $revenuePlan->delete();

            return redirect()->route('erkap.revenue-plans.index')
                ->with('success', 'Rencana pendapatan berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.revenue-plans.index')->with('error', ErrorMessage::from($err));
        }
    }

    protected function availableDivisions()
    {
        return Division::query()
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->where('id', ErkapAccess::divisionId());
            })
            ->get();
    }

    protected function sumMonths(array $data): float
    {
        return array_sum(array_map(fn ($month) => (float) ($data[$month] ?? 0), RevenuePlan::monthColumns()));
    }

    protected function rebuildZbb(?RKAP $rkap): void
    {
        if ($rkap) {
            ZBBReviewService::buildReviews($rkap);
        }
    }
}
