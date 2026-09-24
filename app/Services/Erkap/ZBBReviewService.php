<?php

namespace App\Services\Erkap;

use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RevenuePlan;
use App\Models\Erkap\RoutineCost;
use App\Models\Erkap\WorkProgram;
use App\Models\Erkap\ZBBReview;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ZBBReviewService
{
    public static function previousRkap(RKAP $rkap): ?RKAP
    {
        return RKAP::query()
            ->whereKeyNot($rkap->id)
            ->where('year', '<', $rkap->year)
            ->when($rkap->company_id, function ($query) use ($rkap) {
                $query->where('company_id', $rkap->company_id);
            })
            ->orderByDesc('year')
            ->first();
    }

    public static function buildReviews(RKAP $rkap, ?RKAP $previousRkap = null): array
    {
        $previous = $previousRkap ?? static::previousRkap($rkap);
        $previousMap = static::collectPriorYear($previous);

        $rows = [];
        $total = 0;
        $skippedCount = 0;
        $increaseCount = 0;
        $blockingCount = 0;

        foreach (static::collectCurrent($rkap) as $item) {
            $prior = (float) ($previousMap[$item['key']] ?? 0);
            $proposed = (float) $item['proposed'];
            $delta = round($proposed - $prior, 2);
            $percent = round($prior > 0 ? (($delta / $prior) * 100) : ($proposed > 0 ? 100.00 : 0.00), 2);

            $isIncrease = $percent > 0;
            $existing = ZBBReview::query()
                ->where('erkap_rkap_id', $rkap->id)
                ->where('subject_type', $item['type'])
                ->where('subject_id', $item['id'])
                ->first();

            $status = $isIncrease
                ? (($existing && $existing->zbb_status !== 'skipped') ? $existing->zbb_status : 'pending')
                : 'skipped';

            $rationale = ($isIncrease && $existing) ? $existing->increase_rationale : null;

            ZBBReview::updateOrCreate(
                [
                    'erkap_rkap_id' => $rkap->id,
                    'subject_type' => $item['type'],
                    'subject_id' => $item['id'],
                ],
                [
                    'division_id' => $item['division_id'],
                    'display_name' => $item['display_name'],
                    'prior_year_amount' => $prior,
                    'proposed_amount' => $proposed,
                    'delta_amount' => $delta,
                    'delta_percent' => $percent,
                    'increase_rationale' => $rationale,
                    'zbb_status' => $status,
                ]
            );

            $total++;

            if ($status === 'skipped') {
                $skippedCount++;
            }

            if ($isIncrease) {
                $increaseCount++;

                if (blank($rationale) || $status !== 'approved') {
                    $blockingCount++;
                }
            }

            $rows[] = [
                'subject_type' => $item['type'],
                'subject_id' => $item['id'],
                'prior_year_amount' => $prior,
                'proposed_amount' => $proposed,
                'delta_amount' => $delta,
                'delta_percent' => $percent,
                'zbb_status' => $status,
            ];
        }

        $activeKeys = collect($rows)->mapWithKeys(function (array $row) {
            return ["{$row['subject_type']}.{$row['subject_id']}" => true];
        });

        ZBBReview::query()
            ->where('erkap_rkap_id', $rkap->id)
            ->get()
            ->reject(fn (ZBBReview $review) => isset($activeKeys["{$review->subject_type}.{$review->subject_id}"]))
            ->each->delete();

        return [
            'rkap_id' => $rkap->id,
            'year' => $rkap->year,
            'previous_year' => $previous?->year,
            'total' => $total,
            'skipped' => $skippedCount,
            'increase' => $increaseCount,
            'blocking' => $blockingCount,
            'rows' => $rows,
        ];
    }

    public static function requireRationale(RKAP $rkap): void
    {
        if (! ZBBReview::where('erkap_rkap_id', $rkap->id)->exists()) {
            return;
        }

        $blocking = ZBBReview::query()
            ->where('erkap_rkap_id', $rkap->id)
            ->get()
            ->filter(fn (ZBBReview $review) => $review->blocksConsolidation())
            ->values();

        if ($blocking->isEmpty()) {
            return;
        }

        $sample = $blocking->take(5)
            ->map(fn (ZBBReview $review) => $review->display_name ?: "{$review->subjectTypeLabel()} #{$review->subject_id}")
            ->implode(', ');

        throw new \RuntimeException(
            "Zero Based Budgeting belum selesai untuk RKAP {$rkap->year}: {$blocking->count()} pos anggaran "
            . "memiliki kenaikan yang belum dijustifikasi/disetujui. Contoh: {$sample}. "
            . 'Selesaikan review ZBB (justifikasi + persetujuan) sebelum melakukan konsolidasi anggaran.'
        );
    }

    public static function review(RKAP $rkap, int $id, User $user, array $payload): ZBBReview
    {
        $review = ZBBReview::query()
            ->where('erkap_rkap_id', $rkap->id)
            ->whereKey($id)
            ->firstOrFail();

        $status = $payload['zbb_status'] ?? $review->zbb_status;

        if (! in_array($status, ZBBReview::STATUSES, true)) {
            throw ValidationException::withMessages([
                'zbb_status' => 'Status review ZBB tidak valid.',
            ]);
        }

        $rationale = array_key_exists('increase_rationale', $payload)
            ? trim((string) $payload['increase_rationale'])
            : $review->increase_rationale;

        if ($review->isIncrease() && $status !== 'skipped' && blank($rationale)) {
            throw ValidationException::withMessages([
                'increase_rationale' => 'Justifikasi kenaikan wajib diisi sebelum review disimpan.',
            ]);
        }

        $review->zbb_status = $status;
        $review->increase_rationale = $rationale;
        $review->review_notes = array_key_exists('review_notes', $payload)
            ? ($payload['review_notes'] ?: null)
            : $review->review_notes;

        if (in_array($status, ['approved', 'rejected'], true)) {
            $review->reviewed_by = $user->id;
            $review->reviewed_at = now();
        } else {
            $review->reviewed_by = null;
            $review->reviewed_at = null;
        }

        $review->save();

        return $review->refresh();
    }

    public static function autoSnapshot(): array
    {
        $summaries = [];

        foreach (RKAP::query()->orderBy('year')->get() as $rkap) {
            $summary = static::buildReviews($rkap);

            foreach ($summary['rows'] as $row) {
                $class = match ($row['subject_type']) {
                    'routine_cost' => RoutineCost::class,
                    'investment_plan' => InvestmentPlan::class,
                    'revenue_plan' => RevenuePlan::class,
                    default => null,
                };

                if ($class) {
                    $class::whereKey($row['subject_id'])
                        ->update(['prior_year_amount' => $row['prior_year_amount']]);
                }
            }

            $summaries[] = $summary;
        }

        return $summaries;
    }

    protected static function collectCurrent(RKAP $rkap): array
    {
        $rows = [];

        RoutineCost::query()
            ->with('workProgram.riskIdentification.departmentTarget.division')
            ->whereHas('workProgram.riskIdentification.departmentTarget.companyTarget', function ($query) use ($rkap) {
                $query->where('erkap_rkap_id', $rkap->id);
            })
            ->get()
            ->groupBy(function (RoutineCost $item) {
                $divisionId = $item->workProgram?->riskIdentification?->departmentTarget?->division_id;

                return static::key($divisionId, 'rc', $item->erkap_cost_element_id);
            })
            ->each(function ($group) use (&$rows) {
                $first = $group->first();
                $divisionId = $first->workProgram?->riskIdentification?->departmentTarget?->division_id;
                $rows[] = [
                    'type' => 'routine_cost',
                    'id' => $first->id,
                    'division_id' => $divisionId,
                    'display_name' => $first->need ?: 'Biaya Rutin',
                    'proposed' => (float) $group->sum('total'),
                    'key' => static::key($divisionId, 'rc', $first->erkap_cost_element_id),
                ];
            });

        InvestmentPlan::query()
            ->with('workProgram.riskIdentification.departmentTarget.division')
            ->whereHas('workProgram.riskIdentification.departmentTarget.companyTarget', function ($query) use ($rkap) {
                $query->where('erkap_rkap_id', $rkap->id);
            })
            ->get()
            ->groupBy(function (InvestmentPlan $item) {
                $divisionId = $item->workProgram?->riskIdentification?->departmentTarget?->division_id;

                return static::key($divisionId, 'ip', $item->cost_center_id, $item->name);
            })
            ->each(function ($group) use (&$rows) {
                $first = $group->first();
                $divisionId = $first->workProgram?->riskIdentification?->departmentTarget?->division_id;
                $rows[] = [
                    'type' => 'investment_plan',
                    'id' => $first->id,
                    'division_id' => $divisionId,
                    'display_name' => $first->name ?: 'Rencana Investasi',
                    'proposed' => (float) $group->sum('total'),
                    'key' => static::key($divisionId, 'ip', $first->cost_center_id, $first->name),
                ];
            });

        RevenuePlan::query()->where('erkap_rkap_id', $rkap->id)->get()
            ->groupBy(function (RevenuePlan $item) {
                return static::key($item->division_id, 'rv', $item->chart_of_account_id);
            })
            ->each(function ($group) use (&$rows) {
                $first = $group->first();
                $rows[] = [
                    'type' => 'revenue_plan',
                    'id' => $first->id,
                    'division_id' => $first->division_id,
                    'display_name' => $first->description ?: 'Rencana Pendapatan',
                    'proposed' => (float) $group->sum('total'),
                    'key' => static::key($first->division_id, 'rv', $first->chart_of_account_id),
                ];
            });

        WorkProgram::query()
            ->with('riskIdentification.departmentTarget.division')
            ->whereHas('riskIdentification.departmentTarget.companyTarget', function ($query) use ($rkap) {
                $query->where('erkap_rkap_id', $rkap->id);
            })
            ->get()
            ->each(function (WorkProgram $item) use (&$rows) {
                $divisionId = $item->riskIdentification?->departmentTarget?->division_id;
                $identifier = $item->code ?: $item->name;
                $rows[] = [
                    'type' => 'work_program',
                    'id' => $item->id,
                    'division_id' => $divisionId,
                    'display_name' => $item->name ?: 'Program Kerja',
                    'proposed' => (float) $item->year_plan,
                    'key' => static::key($divisionId, 'wp', $identifier),
                ];
            });

        return $rows;
    }

    protected static function collectPriorYear(?RKAP $previous): array
    {
        if (! $previous) {
            return [];
        }

        $map = [];

        $accumulate = function (string $key, float $amount) use (&$map) {
            $map[$key] = round(($map[$key] ?? 0) + $amount, 2);
        };

        RoutineCost::query()
            ->with('workProgram.riskIdentification.departmentTarget')
            ->whereHas('workProgram.riskIdentification.departmentTarget.companyTarget', function ($query) use ($previous) {
                $query->where('erkap_rkap_id', $previous->id);
            })
            ->get()
            ->each(function (RoutineCost $item) use (&$accumulate) {
                $divisionId = $item->workProgram?->riskIdentification?->departmentTarget?->division_id;
                $accumulate(static::key($divisionId, 'rc', $item->erkap_cost_element_id), (float) $item->total);
            });

        InvestmentPlan::query()
            ->with('workProgram.riskIdentification.departmentTarget')
            ->whereHas('workProgram.riskIdentification.departmentTarget.companyTarget', function ($query) use ($previous) {
                $query->where('erkap_rkap_id', $previous->id);
            })
            ->get()
            ->each(function (InvestmentPlan $item) use (&$accumulate) {
                $divisionId = $item->workProgram?->riskIdentification?->departmentTarget?->division_id;
                $accumulate(static::key($divisionId, 'ip', $item->cost_center_id, $item->name), (float) $item->total);
            });

        RevenuePlan::query()->where('erkap_rkap_id', $previous->id)->get()
            ->each(function (RevenuePlan $item) use (&$accumulate) {
                $accumulate(static::key($item->division_id, 'rv', $item->chart_of_account_id), (float) $item->total);
            });

        WorkProgram::query()
            ->with('riskIdentification.departmentTarget')
            ->whereHas('riskIdentification.departmentTarget.companyTarget', function ($query) use ($previous) {
                $query->where('erkap_rkap_id', $previous->id);
            })
            ->get()
            ->each(function (WorkProgram $item) use (&$accumulate) {
                $divisionId = $item->riskIdentification?->departmentTarget?->division_id;
                $identifier = $item->code ?: $item->name;
                $accumulate(static::key($divisionId, 'wp', $identifier), (float) $item->year_plan);
            });

        return $map;
    }

    protected static function key(...$parts): string
    {
        return implode('|', array_map(fn ($part) => (string) ($part ?? 'x'), $parts));
    }
}