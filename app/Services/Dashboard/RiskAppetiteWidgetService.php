<?php

namespace App\Services\Dashboard;

use App\Models\Erkap\RiskAppetite;
use App\Models\Erkap\RiskAssessmentMonthly;
use App\Services\ErkapAccess;

class RiskAppetiteWidgetService
{
    public function data(?int $rkapId = null, ?int $year = null): array
    {
        $year = $year ?? (int) date('Y');
        $riskIdentificationIds = DashboardScope::riskIdentificationIds($rkapId);

        $appetites = RiskAppetite::query()
            ->with(['riskTaxonomies.riskTypes'])
            ->whereNotNull('threshold_score')
            ->get();

        $gauge = ['value' => 0, 'zone' => 'green', 'label' => 'Tidak ada data'];
        $breachesByAppetite = [];

        foreach ($appetites as $appetite) {
            $taxonomyIds = $appetite->riskTaxonomies->pluck('id')->all();
            $typeIds = $appetite->riskTaxonomies->flatMap->riskTypes->pluck('id')->all();

            $query = RiskAssessmentMonthly::where('year', $year)
                ->whereIn('erkap_risk_identification_id', $riskIdentificationIds ?: [-1])
                ->whereHas('riskIdentification', function ($q) use ($typeIds) {
                    $q->whereIn('erkap_risk_type_id', $typeIds ?: [-1]);
                });

            $total = (int) (clone $query)->count();
            $exposed = (int) (clone $query)->where('inherent_score', '>', $appetite->threshold_score)->count();

            if ($total > 0) {
                $percentage = round(($exposed / $total) * 100, 2);
                $breachesByAppetite[] = [
                    'appetite' => $appetite->name,
                    'threshold' => $appetite->threshold_score,
                    'total' => $total,
                    'exposed' => $exposed,
                    'percentage' => $percentage,
                    'zone' => $this->zone($percentage),
                ];
            }
        }

        if (! empty($breachesByAppetite)) {
            $maxBreach = collect($breachesByAppetite)->sortByDesc('percentage')->first();
            $gauge = [
                'value' => $maxBreach['percentage'],
                'zone' => $maxBreach['zone'],
                'label' => $maxBreach['appetite'],
            ];
        }

        return [
            'year' => $year,
            'gauge' => $gauge,
            'breaches' => $breachesByAppetite,
        ];
    }

    protected function zone(float $percentage): string
    {
        if ($percentage <= 30) {
            return 'green';
        }

        return $percentage <= 60 ? 'yellow' : 'red';
    }
}