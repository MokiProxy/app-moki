<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpensePlanRequest;
use App\Http\Requests\UpdateExpensePlanRequest;
use App\Models\ChartOfAccount;
use App\Models\Division;
use App\Models\Erkap\ExpensePlan;
use App\Models\Erkap\RKAP;
use App\Services\ErkapAccess;
use Exception;
use Illuminate\Support\Facades\Auth;

class ExpensePlanController extends Controller
{
    public function index()
    {
        $pageName = 'Rencana Beban';
        $expensePlans = ExpensePlan::with(['rkap', 'division', 'chartOfAccount'])
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->where('division_id', ErkapAccess::divisionId());
            })
            ->latest()
            ->paginate(10);

        return view('erkap.expense-plan.index', compact('pageName', 'expensePlans'));
    }

    public function create()
    {
        $pageName = 'Buat Rencana Beban';
        $rkaps = RKAP::orderByDesc('year')->get();
        $divisions = $this->availableDivisions();
        $chartOfAccounts = ChartOfAccount::expense()->orderBy('code')->get();

        return view('erkap.expense-plan.create', compact('pageName', 'rkaps', 'divisions', 'chartOfAccounts'));
    }

    public function store(StoreExpensePlanRequest $request)
    {
        try {
            ErkapAccess::assertDivisionAccess($request->integer('division_id'));

            $data = $request->validated();
            $data['total'] = $this->sumMonths($data);
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            ExpensePlan::create($data);

            return redirect()->route('erkap.expense-plans.index')
                ->with('success', 'Rencana beban baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.expense-plans.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(ExpensePlan $expensePlan)
    {
        ErkapAccess::assertDivisionAccess($expensePlan->division_id);

        $pageName = 'Edit Rencana Beban';
        $rkaps = RKAP::orderByDesc('year')->get();
        $divisions = $this->availableDivisions();
        $chartOfAccounts = ChartOfAccount::expense()->orderBy('code')->get();

        return view('erkap.expense-plan.edit', compact('pageName', 'expensePlan', 'rkaps', 'divisions', 'chartOfAccounts'));
    }

    public function update(UpdateExpensePlanRequest $request, ExpensePlan $expensePlan)
    {
        try {
            ErkapAccess::assertDivisionAccess($expensePlan->division_id);
            ErkapAccess::assertDivisionAccess($request->integer('division_id'));

            $data = $request->validated();
            $data['total'] = $this->sumMonths($data);
            $data['updated_by'] = Auth::id();

            $expensePlan->update($data);

            return redirect()->route('erkap.expense-plans.index')
                ->with('success', 'Rencana beban berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.expense-plans.edit', $expensePlan->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(ExpensePlan $expensePlan)
    {
        try {
            ErkapAccess::assertDivisionAccess($expensePlan->division_id);

            $expensePlan->delete();

            return redirect()->route('erkap.expense-plans.index')
                ->with('success', 'Rencana beban berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.expense-plans.index')->with('error', $err->getMessage());
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
        return array_sum(array_map(fn ($month) => (float) ($data[$month] ?? 0), ExpensePlan::monthColumns()));
    }
}
