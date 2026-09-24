<?php

namespace App\Services;

use App\Models\Erkap\RiskIdentification;
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

        $user = $user ?: auth()->user();

        if (static::canOverride($user)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => 'Form 1 dalam status '.$risk->statusLabel().' dan tidak dapat diubah. Hubungi E-RKAP Admin bila diperlukan perbaikan.',
        ]);
    }
}
