<?php

namespace App\Services\Dashboard;

use App\Models\Erkap\RiskAssessmentMonthly;

class RiskTrendWidgetService
{
    public const LEVELS = ['VL', 'L', 'M', 'H', 'VH'];

    public function data(?int $rkapId = null, ?int $year = null, array $filters = []): array
    {
        $year = $year ?? (int) date('Y');
        $riskIdentificationIds = DashboardScope::riskIdentificationIds($rkapId);

        $query = RiskAssessmentMonthly::where('year', $year)
            ->whereIn('erkap_risk_identification_id', $riskIdentificationIds ?: [-1]);

        if (! empty($filters['division_id'])) {
            $query->whereHas('riskIdentification.departmentTarget', fn ($q) => $q->where('division_id', $filters['division_id']));
        }

        if (! empty($filters['taxonomy_id'])) {
            $query->whereHas('riskIdentification', fn ($q) => $q->where('erkap_risk_taxonomy_id', $filters['taxonomy_id']));
        }

        if (! empty($filters['risk_type_id'])) {
            $query->whereHas('riskIdentification', fn ($q) => $q->where('erkap_risk_type_id', $filters['risk_type_id']));
        }

        $rows = (clone $query)
            ->selectRaw('month, inherent_probability, inherent_impact, COUNT(*) as total')
            ->groupBy('month', 'inherent_probability', 'inherent_impact')
            ->get();

        $matrix = [];
        foreach (self::LEVELS as $level) {
            $matrix[$level] = array_fill(0, 12, 0);
        }

        foreach ($rows as $row) {
            $index = max(0, min(11, (int) $row->month - 1));
            $score = (int) $row->inherent_probability * (int) $row->inherent_impact;
            $matrix[$this->scoreLevel($score)][$index] += (int) $row->total;
        }

        return [
            'year' => $year,
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            'levels' => self::LEVELS,
            'series' => $matrix,
            'totals' => array_map('array_sum', $matrix),
        ];
    }

    public function scoreLevel(int $score): string
    {
        return match (true) {
            $score >= 21 => 'VH',
            $score >= 16 => 'H',
            $score >= 11 => 'M',
            $score >= 6 => 'L',
            default => 'VL',
        };
    }
}