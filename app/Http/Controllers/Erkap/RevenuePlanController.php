<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRevenuePlanRequest;
use App\Http\Requests\UpdateRevenuePlanRequest;
use App\Models\ChartOfAccount;
use App\Models\Division;
use App\Models\Erkap\RevenuePlan;
use App\Models\Erkap\RKAP;
use App\Services\ErkapAccess;
use Exception;
use Illuminate\Support\Facades\Auth;

class RevenuePlanController extends Controller
{
    public function index()
    {
        $pageName = 'Rencana Pendapatan';
        $revenuePlans = RevenuePlan::with(['rkap', 'division', 'chartOfAccount'])
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->where('division_id', ErkapAccess::divisionId());
            })
            ->latest()
            ->paginate(10);

        return view('erkap.revenue-plan.index', compact('pageName', 'revenuePlans'));
    }

    public function create()
    {
        $pageName = 'Buat Rencana Pendapatan';
        $rkaps = RKAP::orderByDesc('year')->get();
        $divisions = $this->availableDivisions();
        $chartOfAccounts = ChartOfAccount::revenue()->orderBy('code')->get();

        return view('erkap.revenue-plan.create', compact('pageName', 'rkaps', 'divisions', 'chartOfAccounts'));
    }

    public function store(StoreRevenuePlanRequest $request)
    {
        try {
            ErkapAccess::assertDivisionAccess($request->integer('division_id'));

            $data = $request->validated();
            $data['total'] = $this->sumMonths($data);
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            RevenuePlan::create($data);

            return redirect()->route('erkap.revenue-plans.index')
                ->with('success', 'Rencana pendapatan baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.revenue-plans.create')
                ->withInput()
                ->with('error', $err->getMessage())
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
        $chartOfAccounts = ChartOfAccount::revenue()->orderBy('code')->get();

        return view('erkap.revenue-plan.edit', compact('pageName', 'revenuePlan', 'rkaps', 'divisions', 'chartOfAccounts'));
    }

    public function update(UpdateRevenuePlanRequest $request, RevenuePlan $revenuePlan)
    {
        try {
            ErkapAccess::assertDivisionAccess($revenuePlan->division_id);
            ErkapAccess::assertDivisionAccess($request->integer('division_id'));

            $data = $request->validated();
            $data['total'] = $this->sumMonths($data);
            $data['updated_by'] = Auth::id();

            $revenuePlan->update($data);

            return redirect()->route('erkap.revenue-plans.index')
                ->with('success', 'Rencana pendapatan berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.revenue-plans.edit', $revenuePlan->id)
                ->withInput()
                ->with('error', $err->getMessage())
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
            return redirect()->route('erkap.revenue-plans.index')->with('error', $err->getMessage());
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
}
