<?php

namespace App\Services\Dashboard;

use App\Models\Erkap\RKAP;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\WorkProgram;
use App\Services\ErkapAccess;

class DashboardScope
{
    public static function riskIdentificationIds(?int $rkapId = null, ?RKAP $rkap = null): array
    {
        if ($rkapId && ! $rkap) {
            $rkap = RKAP::find($rkapId);
        }

        $query = RiskIdentification::query()
            ->whereIn('id', ErkapAccess::riskIdentificationIds() ?: [-1]);

        if ($rkap) {
            $query->whereHas('departmentTarget.companyTarget', fn ($q) => $q->where('erkap_rkap_id', $rkap->id));
        }

        return $query->pluck('id')->all();
    }

    public static function workProgramIds(?int $rkapId = null, ?RKAP $rkap = null): array
    {
        if ($rkapId && ! $rkap) {
            $rkap = RKAP::find($rkapId);
        }

        $query = WorkProgram::query()
            ->whereIn('id', ErkapAccess::workProgramIds() ?: [-1]);

        if ($rkap) {
            $query->whereHas('riskIdentification.departmentTarget.companyTarget', fn ($q) => $q->where('erkap_rkap_id', $rkap->id));
        }

        return $query->pluck('id')->all();
    }
}