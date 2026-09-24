<?php

namespace App\Services\Erkap;

use App\Models\Erkap\RKAP;
use App\Models\Erkap\WorkProgram;
use App\Models\User;
use App\Notifications\RkapLifecycleNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class RKAPLifecycleService
{
    public const BMI_ROLES = ['erkap-gate-review', 'erkap-bmi-admin'];

    public const META_FIELDS = [
        'kickoff_date',
        'kickoff_notes',
        'direction_notes',
        'bmi_notes',
    ];

    public static function advance(RKAP $rkap, ?array $meta = null): RKAP
    {
        $next = $rkap->nextPhase();

        if (! $next) {
            throw new \RuntimeException('Periode RKAP sudah berada pada fase akhir dan tidak dapat di-advance lagi.');
        }

        if (! $rkap->canTransitionTo($next)) {
            throw ValidationException::withMessages([
                'phase' => 'Fase RKAP harus dijalankan berurutan (inisiasi → penyusunan → konsolidasi → finalisasi → pengesahan).',
            ]);
        }

        $payload = [
            'phase' => $next,
            'phase_started_at' => now(),
        ];

        if ($next === 'approved' && ! $rkap->resolution_date) {
            $payload['resolution_date'] = now()->toDateString();
        }

        foreach (static::META_FIELDS as $field) {
            if (is_array($meta) && array_key_exists($field, $meta)) {
                $payload[$field] = $meta[$field];
            }
        }

        $rkap->update($payload);

        return $rkap->refresh();
    }

    public static function resetPhase(RKAP $rkap): RKAP
    {
        if (! in_array($rkap->status, ['draft', 'rejected'], true)) {
            throw new \RuntimeException('Reset fase hanya dapat dilakukan saat status dokumen masih Draft atau Ditolak.');
        }

        $rkap->update([
            'phase' => 'initiation',
            'phase_started_at' => now(),
            'bmi_alignment_status' => 'none',
            'bmi_notes' => null,
            'distribution_status' => 'not_distributed',
        ]);

        return $rkap->refresh();
    }

    public static function markBmiAligned(RKAP $rkap, User $user, array $payload): RKAP
    {
        static::assertBmiRole($user);

        $rkap->update([
            'bmi_alignment_status' => $payload['bmi_alignment_status'],
            'bmi_notes' => $payload['bmi_notes'] ?? null,
        ]);

        return $rkap->refresh();
    }

    public static function assertBmiRole(User $user): void
    {
        if (! $user->hasAnyRole(static::BMI_ROLES)) {
            throw ValidationException::withMessages([
                'bmi_alignment_status' => 'Hanya role Gate Review PT BMI atau BMI Admin yang dapat mengisi alignment PT BMI.',
            ]);
        }
    }

    public static function distribute(RKAP $rkap, User $user): RKAP
    {
        if ($rkap->distribution_status === 'distributed') {
            throw new \RuntimeException('Dokumen RKAP ini sudah ditandai tersebar.');
        }

        $rkap->distribution_status = 'distributed';

        if (! $rkap->resolution_date) {
            $rkap->resolution_date = now()->toDateString();
        }

        $rkap->save();

        Notification::send(
            User::role('erkap-admin')->get(),
            new RkapLifecycleNotification($rkap, 'distributed')
        );

        return $rkap->refresh();
    }

    public static function resolveForWorkProgram(?int $workProgramId): ?RKAP
    {
        if (! $workProgramId) {
            return null;
        }

        $companyTarget = WorkProgram::query()
            ->with('riskIdentification.departmentTarget.companyTarget.rkap')
            ->find($workProgramId)
            ?->riskIdentification
            ?->departmentTarget
            ?->companyTarget;

        return $companyTarget?->rkap;
    }

    public static function assertNotLocked(?RKAP $rkap, string $module = 'data anggaran'): void
    {
        if ($rkap && $rkap->isLockedForInput()) {
            throw ValidationException::withMessages([
                'rkap' => "{$module} untuk periode RKAP {$rkap->year} sudah terkunci karena berada pada fase \"{$rkap->phaseLabel()}\". Data tidak dapat ditambah atau diubah.",
            ]);
        }
    }
}