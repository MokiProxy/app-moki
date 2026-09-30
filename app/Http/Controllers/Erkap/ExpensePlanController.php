<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpensePlanRequest;
use App\Http\Requests\UpdateExpensePlanRequest;
use App\Models\Division;
use App\Models\Erkap\ExpensePlan;
use App\Models\Erkap\RKAP;
use App\Services\ErkapAccess;
use App\Support\ErrorMessage;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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

        return view('erkap.expense-plan.create', compact('pageName', 'rkaps', 'divisions'));
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
        } catch (ValidationException $err) {
            // `back()` bergantung pada header Referer, jadi pada request tanpa
            // asal (API, test) ia melompat ke root. Redirect eksplisit ke form
            // menjaga pesan error tetap terlihat di halaman yang benar.
            return redirect()->route('erkap.expense-plans.create')
                ->withInput()
                ->with('error', ErrorMessage::from($err))
                ->withErrors($err->errors());
        } catch (Exception $err) {
            return redirect()->route('erkap.expense-plans.create')
                ->withInput()
                ->with('error', ErrorMessage::from($err))
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

        return view('erkap.expense-plan.edit', compact('pageName', 'expensePlan', 'rkaps', 'divisions'));
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
        } catch (ValidationException $err) {
            return redirect()->route('erkap.expense-plans.edit', $expensePlan->id)
                ->withInput()
                ->with('error', ErrorMessage::from($err))
                ->withErrors($err->errors());
        } catch (Exception $err) {
            return redirect()->route('erkap.expense-plans.edit', $expensePlan->id)
                ->withInput()
                ->with('error', ErrorMessage::from($err))
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
            return redirect()->route('erkap.expense-plans.index')->with('error', ErrorMessage::from($err));
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
