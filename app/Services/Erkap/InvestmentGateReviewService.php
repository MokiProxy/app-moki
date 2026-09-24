<?php

namespace App\Services\Erkap;

use App\Models\Erkap\Approval;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\InvestmentStageGate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestmentGateReviewService
{
    public const STAGES = [
        'proposal' => ['order' => 1, 'role' => 'erkap-ppk'],
        'cba' => ['order' => 2, 'role' => 'erkap-ppk'],
        'aset' => ['order' => 3, 'role' => 'erkap-manajemen-aset'],
        'direksi_keuangan' => ['order' => 4, 'role' => 'erkap-direksi-keuangan'],
        'gate_review_bmi' => ['order' => 5, 'role' => 'erkap-gate-review'],
    ];

    public static function roleForStage(string $stage): ?string
    {
        return static::STAGES[$stage]['role'] ?? null;
    }

    public static function defaultStages(): array
    {
        return collect(static::STAGES)
            ->mapWithKeys(fn (array $meta, string $stage) => [
                $stage => [
                    'stage' => $stage,
                    'stage_order' => $meta['order'],
                    'reviewer_role' => $meta['role'],
                ],
            ])
            ->all();
    }

    public static function initialize(InvestmentPlan $plan, bool $reset = false): void
    {
        if ($plan->stageGates()->exists()) {
            if ($reset && in_array($plan->status, ['rejected', 'revised'], true)) {
                DB::transaction(function () use ($plan) {
                    $plan->stageGates()->update([
                        'status' => 'pending',
                        'reviewed_by' => null,
                        'reviewed_at' => null,
                        'notes' => null,
                        'result' => null,
                    ]);
                    $plan->update(['gate_review_status' => 'in_review']);
                });
            }

            return;
        }

        DB::transaction(function () use ($plan) {
            foreach (static::defaultStages() as $data) {
                InvestmentStageGate::create(['erkap_investment_plan_id' => $plan->id] + $data);
            }

            $plan->update(['gate_review_status' => 'in_review']);
        });
    }

    public static function aggregateStatus(InvestmentPlan $plan): string
    {
        if ($plan->stageGates()->count() === 0) {
            return 'none';
        }

        $statuses = $plan->stageGates()->pluck('status');

        if ($statuses->contains(fn ($status) => in_array($status, ['rejected', 'revised'], true))) {
            return 'rejected';
        }

        if ($statuses->every(fn ($status) => $status === 'approved')) {
            return 'approved';
        }

        if ($statuses->contains('approved')) {
            return 'partial';
        }

        return 'in_review';
    }

    public static function review(InvestmentPlan $plan, string $stage, User $user, array $payload): InvestmentStageGate
    {
        $gate = $plan->stageGates()->where('stage', $stage)->first();

        if (! $gate) {
            throw ValidationException::withMessages([
                'stage' => 'Stage gate tidak ditemukan untuk rencana investasi ini.',
            ]);
        }

        if (! $gate->canReviewBy($user)) {
            throw ValidationException::withMessages([
                'stage' => 'Anda tidak memiliki role yang berhak menilai gate ini.',
            ]);
        }

        if ($gate->status === 'approved') {
            throw ValidationException::withMessages([
                'stage' => 'Gate ini sudah disetujui.',
            ]);
        }

        $earlierPending = $plan->stageGates()
            ->where('stage_order', '<', $gate->stage_order)
            ->where('status', '!=', 'approved')
            ->count();

        if ($earlierPending > 0) {
            throw ValidationException::withMessages([
                'stage' => 'Stage gate sebelumnya belum disetujui.',
            ]);
        }

        $status = $payload['status'] ?? 'approved';
        $result = $payload['result'] ?? null;
        $notes = $payload['notes'] ?? null;

        DB::transaction(function () use ($plan, $gate, $user, $status, $result, $notes) {
            $gate->update([
                'status' => $status,
                'result' => $result,
                'notes' => $notes,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ]);

            if ($status === 'approved') {
                static::approveMatchingApproval($plan, $gate, $user);
            }

            $plan->refresh();
            $aggregate = static::aggregateStatus($plan);

            if ($aggregate === 'approved') {
                $plan->approvals()->where('status', 'pending')->get()
                    ->each(function (Approval $approval) use ($user) {
                        $approval->update([
                            'status' => 'approved',
                            'approver_id' => $user->id,
                            'approved_at' => now(),
                            'notes' => $approval->notes ?: 'Disetujui melalui Gate Review.',
                        ]);
                    });
                $plan->update(['status' => 'approved', 'gate_review_status' => 'approved']);
            } elseif (in_array($status, ['rejected', 'revised'], true)) {
                $plan->approvals()->where('status', 'pending')->update(['status' => 'rejected']);
                $plan->update(['status' => $status, 'gate_review_status' => 'rejected']);
            } else {
                $plan->update(['gate_review_status' => $aggregate]);
            }
        });

        return $gate->refresh();
    }

    public static function approveMatchingApproval(InvestmentPlan $plan, InvestmentStageGate $gate, User $user): void
    {
        $role = $gate->reviewer_role;

        $groupHasPending = $plan->stageGates()
            ->where('reviewer_role', $role)
            ->where('status', '!=', 'approved')
            ->doesntExist() === false;

        if ($groupHasPending) {
            return;
        }

        $approval = $plan->approvals()
            ->where('role', $role)
            ->where('status', 'pending')
            ->orderBy('level')
            ->first();

        if ($approval) {
            $approval->update([
                'status' => 'approved',
                'approver_id' => $user->id,
                'approved_at' => now(),
                'notes' => 'Disetujui melalui Gate Review.',
            ]);
        }
    }
}