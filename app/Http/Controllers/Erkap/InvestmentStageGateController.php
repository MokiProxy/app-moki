<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\Erkap\InvestmentStageGateReviewRequest;
use App\Models\Erkap\InvestmentStageGate;
use App\Services\Erkap\InvestmentGateReviewService;
use Exception;
use Illuminate\Http\Request;

class InvestmentStageGateController extends Controller
{
    public function index(Request $request)
    {
        $pageName = 'Gate Review / Kelayakan Investasi';

        $user = auth()->user();
        $isPrivileged = $user->hasAnyRole(['super-admin', 'admin', 'erkap-admin', 'erkap-auditor']);

        $gates = InvestmentStageGate::query()
            ->with(['plan.workProgram', 'reviewer'])
            ->when(! $isPrivileged, function ($query) use ($user) {
                $query->forRoles($user->getRoleNames()->all());
            })
            ->when($request->filled('erkap_investment_plan_id'), function ($query) use ($request) {
                $query->where('erkap_investment_plan_id', $request->integer('erkap_investment_plan_id'));
            })
            ->orderBy('erkap_investment_plan_id')
            ->orderBy('stage_order')
            ->paginate(15);

        return view('erkap.investment-gate.index', compact('pageName', 'gates'));
    }

    public function show(InvestmentStageGate $gate)
    {
        $user = auth()->user();

        if (! $gate->canReviewBy($user) && ! $user->hasAnyRole(['super-admin', 'admin', 'erkap-admin', 'erkap-auditor'])) {
            abort(403);
        }

        $pageName = 'Evaluasi Gate: '.$gate->label();
        $plan = $gate->plan->load([
            'workProgram',
            'costCenter',
            'investattionCategory',
            'investationType',
            'investationCriteria',
            'stageGates.reviewer',
            'approvals.approver',
        ]);

        $canReview = $plan->status === 'submitted'
            && $gate->status === 'pending'
            && $gate->canReviewBy($user);

        return view('erkap.investment-gate.show', compact('pageName', 'plan', 'gate', 'canReview'));
    }

    public function store(InvestmentStageGateReviewRequest $request, InvestmentStageGate $gate)
    {
        try {
            $plan = $gate->plan;
            $payload = $request->validated();

            if ($gate->stage === 'cba' && $request->hasFile('cba_attachment')) {
                $plan->update([
                    'cba_attachment_path' => $request->file('cba_attachment')->store('investment-plans/cba', 'public'),
                ]);
            }

            InvestmentGateReviewService::review($plan, $gate->stage, auth()->user(), $payload);

            return redirect()->route('erkap.investment-gates.index')
                ->with('success', 'Evaluasi "'.$gate->label().'" berhasil disimpan.');
        } catch (Exception $err) {
            return redirect()->route('erkap.investment-gates.show', $gate->id)
                ->withInput()
                ->with('error', $err->getMessage());
        }
    }
}