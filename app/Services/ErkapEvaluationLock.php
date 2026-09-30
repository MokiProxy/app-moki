<?php

namespace App\Services;

use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RoutineCost;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ErkapEvaluationLock
{
    public const LOCKED_STATUSES = ['submitted', 'approved'];

    public static function isEvaluated(RiskIdentification $risk): bool
    {
        return in_array($risk->status, self::LOCKED_STATUSES, true);
    }

    public static function canOverride(?User $user): bool
    {
        return $user !== null
            && $user->hasAnyRole(['super-admin', 'admin', 'erkap-admin', 'erkap-auditor']);
    }

    public static function assertRiskEditable(?RiskIdentification $risk, ?User $user = null): void
    {
        if (! $risk || ! static::isEvaluated($risk)) {
            return;
        }

        static::assertStatusEditable($risk->status, 'Form 1', $risk->statusLabel(), $user);
    }

    /**
     * Kunci yang sama untuk dokumen lain yang sudah masuk rantai approval.
     *
     * Tanpa ini, Biaya Rutin yang sudah disetujui masih bisa diubah atau
     * dihapus oleh pengajinya: angka yang sudah tervalidasi di Laporan Laba
     * Rugi bisa berbeda dari angka yang disetujui PPK/Controller.
     */
    public static function assertRoutineCostEditable(RoutineCost $routineCost, ?User $user = null): void
    {
        if (! in_array($routineCost->status, self::LOCKED_STATUSES, true)) {
            return;
        }

        static::assertStatusEditable(
            $routineCost->status,
            'Biaya rutin',
            ucfirst((string) $routineCost->status),
            $user
        );
    }

    protected static function assertStatusEditable(string $status, string $label, string $statusLabel, ?User $user = null): void
    {
        $user = $user ?: auth()->user();

        if (static::canOverride($user)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => "{$label} dalam status {$statusLabel} dan tidak dapat diubah. Hubungi E-RKAP Admin bila diperlukan perbaikan.",
        ]);
    }
}
