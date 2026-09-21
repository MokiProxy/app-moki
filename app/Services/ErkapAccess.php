<?php

namespace App\Services;

use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\WorkProgram;
use Illuminate\Support\Facades\Auth;

class ErkapAccess
{
    public static function isDivisionScoped(): bool
    {
        return Auth::check() && Auth::user()->hasRole('erkap-cost-owner');
    }

    public static function divisionId(): ?int
    {
        return Auth::user()?->employee?->division_id;
    }

    public static function departmentTargetIds(): array
    {
        if (! static::isDivisionScoped()) {
            return DepartmentTarget::pluck('id')->all();
        }

        $divisionId = static::divisionId();

        return $divisionId
            ? DepartmentTarget::where('division_id', $divisionId)->pluck('id')->all()
            : [];
    }

    public static function riskIdentificationIds(): array
    {
        if (! static::isDivisionScoped()) {
            return RiskIdentification::pluck('id')->all();
        }

        $departmentTargetIds = static::departmentTargetIds();

        return $departmentTargetIds
            ? RiskIdentification::whereIn('erkap_department_target_id', $departmentTargetIds)->pluck('id')->all()
            : [];
    }

    public static function workProgramIds(): array
    {
        if (! static::isDivisionScoped()) {
            return WorkProgram::pluck('id')->all();
        }

        $riskIdentificationIds = static::riskIdentificationIds();

        return $riskIdentificationIds
            ? WorkProgram::whereIn('erkap_risk_identification_id', $riskIdentificationIds)->pluck('id')->all()
            : [];
    }

    public static function assertDepartmentTargetAccess(?int $departmentTargetId): void
    {
        if (static::isDivisionScoped()
            && $departmentTargetId
            && ! in_array($departmentTargetId, static::departmentTargetIds(), true)) {
            abort(403);
        }
    }

    public static function assertRiskIdentificationAccess(?int $riskIdentificationId): void
    {
        if (static::isDivisionScoped()
            && $riskIdentificationId
            && ! in_array($riskIdentificationId, static::riskIdentificationIds(), true)) {
            abort(403);
        }
    }

    public static function assertWorkProgramAccess(?int $workProgramId): void
    {
        if (static::isDivisionScoped()
            && $workProgramId
            && ! in_array($workProgramId, static::workProgramIds(), true)) {
            abort(403);
        }
    }

    public static function assertDivisionAccess(?int $divisionId): void
    {
        if (static::isDivisionScoped()
            && $divisionId
            && $divisionId !== static::divisionId()) {
            abort(403);
        }
    }
}