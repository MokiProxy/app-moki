<?php

namespace App\Services;

use App\Models\Division;
use App\Models\Erkap\Approval;
use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RoutineCost;
use App\Models\Erkap\WorkProgram;
use App\Models\User;
use App\Notifications\ApprovalNotification;
use App\Services\Erkap\InvestmentGateReviewService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ApprovalService
{
    public static function getApprovalMatrix(): array
    {
        return [
            'work_program' => [
                1 => 'erkap-ppk',
                2 => 'erkap-controller',
            ],
            'routine_cost' => [
                1 => 'erkap-ppk',
                2 => 'erkap-controller',
            ],
            'investment_plan' => [
                1 => 'erkap-ppk',
                2 => 'erkap-manajemen-aset',
                3 => 'erkap-direksi-keuangan',
            ],
            'rkap' => [
                1 => 'erkap-komisaris',
                2 => 'erkap-direksi',
            ],
            'risk_register' => [
                1 => 'erkap-risk-manager',
            ],
        ];
    }

    public static function documentTypes(): array
    {
        return [
            'work_program' => [
                'model' => WorkProgram::class,
                'label' => 'Program Kerja',
                'title' => fn ($model) => $model->name,
            ],
            'routine_cost' => [
                'model' => RoutineCost::class,
                'label' => 'Biaya Rutin',
                'title' => fn ($model) => $model->need,
            ],
            'investment_plan' => [
                'model' => InvestmentPlan::class,
                'label' => 'Rencana Investasi',
                'title' => fn ($model) => $model->name,
            ],
            'rkap' => [
                'model' => RKAP::class,
                'label' => 'Periode RKAP',
                'title' => fn ($model) => 'RKAP '.$model->year,
            ],
            'risk_register' => [
                'model' => RiskIdentification::class,
                'label' => 'Register Risiko',
                'title' => fn ($model) => $model->risk,
            ],
        ];
    }

    public static function typeFor($model): ?string
    {
        foreach (static::documentTypes() as $type => $meta) {
            if ($model instanceof $meta['model']) {
                return $type;
            }
        }

        return null;
    }

    public static function resolveModel(string $type, $id): Model
    {
        $meta = static::documentTypes()[$type] ?? null;

        abort_unless($meta, 404);

        return $meta['model']::findOrFail($id);
    }

    public static function submit(Model $model): void
    {
        $type = static::typeFor($model);

        if (! $type) {
            throw new \InvalidArgumentException('Tipe dokumen tidak didukung untuk approval.');
        }

        if (in_array($model->status, ['submitted', 'approved'], true)) {
            throw new \RuntimeException('Dokumen sudah diajukan dan tidak dapat diajukan ulang dalam status saat ini.');
        }

        if ($type === 'investment_plan' && method_exists($model, 'hasProposal') && ! $model->hasProposal()) {
            throw new \RuntimeException('Usulan investasi wajib melampirkan proposal sebelum dapat diajukan.');
        }

        if ($type === 'risk_register' && method_exists($model, 'validateHasStrategyAndWorkProgram')) {
            $model->validateHasStrategyAndWorkProgram();
        }

        if ($type === 'work_program') {
            $model->canSubmitForApproval();
        }

        $matrix = static::getApprovalMatrix()[$type];
        $divisionId = static::divisionIdFor($model);

        $approvals = [];

        DB::beginTransaction();

        try {
            if ($type === 'investment_plan' && method_exists($model, 'stageGates')) {
                InvestmentGateReviewService::initialize($model, true);
            }

            $model->update(['status' => 'submitted']);
            $model->approvals()->delete();

            foreach ($matrix as $level => $role) {
                $approver = static::getApproverByRole($role, $divisionId);

                if (! $approver) {
                    throw new \RuntimeException("Tidak ditemukan approver untuk level {$level} ({$role}). Pastikan role sudah di-assign ke user.");
                }

                $approvals[$level] = $model->approvals()->create([
                    'level' => $level,
                    'role' => $role,
                    'status' => 'pending',
                    'approver_id' => $approver->id,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        static::notifyApprovers($model, $type, $approvals, 'submitted');
    }

    public static function submitBatch(iterable $models): array
    {
        $results = ['submitted' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []];

        foreach ($models as $model) {
            if (! $model->canBeSubmitted()) {
                $results['skipped']++;

                continue;
            }

            try {
                static::submit($model);
                $results['submitted']++;
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['errors'][] = $e->getMessage();
            }
        }

        return $results;
    }

    public static function approve(Model $model, User $user, ?string $notes = null): void
    {
        $approval = static::requireTurn($model, $user);
        $type = static::typeFor($model);

        if ($type === 'investment_plan' && method_exists($model, 'stageGates')) {
            static::assertGateForRoleApproved($model, $approval->role);
        }

        DB::beginTransaction();

        try {
            $approval->update([
                'status' => 'approved',
                'approved_at' => now(),
                'notes' => $notes ?: null,
            ]);

            $totalLevels = $model->approvals()->count();
            $approvedLevels = $model->approvals()->where('status', 'approved')->count();

            if ($approvedLevels >= $totalLevels) {
                $model->update(['status' => 'approved']);
            } else {
                $next = $model->approvals()->where('status', 'pending')->orderBy('level')->first();

                if ($next) {
                    Notification::send($next->approver, new ApprovalNotification($model, static::typeFor($model), 'submitted'));
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public static function reject(Model $model, User $user, ?string $notes = null): void
    {
        $approval = static::requireTurn($model, $user);

        $pendingApproverIds = $model->approvals()
            ->where('status', 'pending')
            ->pluck('approver_id')
            ->reject(fn ($id) => (int) $id === $user->id)
            ->values();

        DB::beginTransaction();

        try {
            $approval->update([
                'status' => 'rejected',
                'approved_at' => now(),
                'notes' => $notes ?: null,
            ]);

            $model->approvals()
                ->where('status', 'pending')
                ->update(['status' => 'rejected']);

            $model->update(['status' => 'rejected']);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        User::whereIn('id', $pendingApproverIds)->get()
            ->each(function (User $approver) use ($model) {
                Notification::send($approver, new ApprovalNotification($model, static::typeFor($model), 'rejected'));
            });
    }

    protected static function assertGateForRoleApproved(Model $model, string $role): void
    {
        $groupTotal = $model->stageGates()->where('reviewer_role', $role)->count();
        $groupApproved = $model->stageGates()->where('reviewer_role', $role)->where('status', 'approved')->count();

        if ($groupTotal === 0 || $groupApproved < $groupTotal) {
            throw new \RuntimeException('Stage Gate untuk level ini belum disetujui. Selesaikan Gate Review terlebih dahulu.');
        }
    }

    protected static function requireTurn(Model $model, User $user): Approval
    {
        if (! in_array($model->status, ['submitted'], true)) {
            throw new \RuntimeException('Dokumen tidak dalam status menunggu persetujuan.');
        }

        $pending = $model->approvals()
            ->where('status', 'pending')
            ->orderBy('level')
            ->first();

        if (! $pending) {
            throw new \RuntimeException('Tidak ada approval yang menunggu untuk dokumen ini.');
        }

        if ((int) $pending->approver_id !== $user->id) {
            throw new \RuntimeException('Bukan giliran Anda untuk memberikan persetujuan dokumen ini.');
        }

        return $pending;
    }

    public static function getApproverByRole(string $role, ?int $divisionId = null): ?User
    {
        $query = function () use ($role, $divisionId) {
            $query = User::query()->role($role);

            if ($divisionId) {
                $query->whereHas('employee', fn ($q) => $q->where('division_id', $divisionId));
            }

            return $query->first();
        };

        $user = $query();

        if (! $user && $divisionId) {
            $user = User::query()->role($role)->first();
        }

        return $user;
    }

    public static function divisionIdFor(Model $model): ?int
    {
        if ($model instanceof RKAP) {
            return null;
        }

        if ($model instanceof RiskIdentification) {
            return $model->departmentTarget?->division_id;
        }

        $workProgram = $model instanceof WorkProgram
            ? $model
            : (method_exists($model, 'workProgram') ? $model->workProgram : null);

        return $workProgram?->riskIdentification?->departmentTarget?->division_id;
    }

    public static function divisionFor(Model $model): ?Division
    {
        if ($model instanceof RKAP) {
            return null;
        }

        if ($model instanceof RiskIdentification) {
            return $model->departmentTarget?->division;
        }

        $workProgram = $model instanceof WorkProgram
            ? $model
            : (method_exists($model, 'workProgram') ? $model->workProgram : null);

        return $workProgram?->riskIdentification?->departmentTarget?->division;
    }

    protected static function notifyApprovers(Model $model, string $type, array $approvals, string $context): void
    {
        $levelOne = $approvals[1] ?? null;

        if ($levelOne && $levelOne->approver) {
            Notification::send($levelOne->approver, new ApprovalNotification($model, $type, $context));
        }
    }
}
