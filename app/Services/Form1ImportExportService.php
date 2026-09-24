<?php

namespace App\Services;

use App\Enums\ErkapRatingLevel;
use App\Enums\ErkapRiskTreatmentType;
use App\Imports\Erkap\Form1Import;
use App\Models\Erkap\CompanyTarget;
use App\Models\Erkap\DepartmentRiskStrategy;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\RatingCriteria;
use App\Models\Erkap\RiskAnalysis;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskIdentificationImpact;
use App\Models\Erkap\RiskIdentificationReason;
use App\Models\Erkap\RiskImpact;
use App\Models\Erkap\RiskProbability;
use App\Models\Erkap\RiskScoreLevel;
use App\Models\Erkap\RiskAppetite;
use App\Models\Erkap\RiskTaxonomy;
use App\Models\Erkap\RiskType;
use App\Models\Erkap\WorkProgram;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class Form1ImportExportService
{
    public const HEADINGS = [
        'No',
        'Sasaran Perusahaan',
        'Sasaran Satuan Kerja',
        'Rating',
        'Identifikasi Risiko',
        'Positif/Negatif',
        'Tipe Risiko',
        'Taksonomi',
        'Penyebab',
        'Dampak',
        'Probabilitas (1-5)',
        'Dampak (1-5)',
        'Nilai Risiko',
        'Peringkat',
        'Strategi',
        'Program Kerja',
    ];

    private const STRATEGY_MAP = [
        'avoidance' => 'avoidance',
        'avoid' => 'avoidance',
        'hindari' => 'avoidance',
        'reduction' => 'reduction',
        'reduce' => 'reduction',
        'mitigate' => 'reduction',
        'kurangi' => 'reduction',
        'mitigasi' => 'reduction',
        'sharing' => 'sharing',
        'transfer' => 'sharing',
        'transferkan' => 'sharing',
        'berbagi' => 'sharing',
        'acceptance' => 'acceptance',
        'accept' => 'acceptance',
        'terima' => 'acceptance',
    ];

    private const DIRECTION_MAP = [
        'positif' => 'positive',
        'positive' => 'positive',
        'negatif' => 'negative',
        'negative' => 'negative',
    ];

    private const LEVEL_LONG_TO_SHORT = [
        'Low' => 'L',
        'Low To Moderate' => 'ML',
        'Moderate' => 'M',
        'Moderate To High' => 'MH',
        'High' => 'H',
    ];

    private const LEVEL_SHORT_TO_LONG = [
        'L' => 'Low',
        'ML' => 'Low To Moderate',
        'M' => 'Moderate',
        'MH' => 'Moderate To High',
        'H' => 'High',
    ];

    /**
     * Render a stored long level label as the short CSV code.
     */
    protected function levelShort(?string $level): string
    {
        return self::LEVEL_LONG_TO_SHORT[$level ?? ''] ?? ($level ?? '-');
    }

    /**
     * Import flat Form 1 rows into normalized entities inside a transaction.
     *
     * @return int number of processed rows (risks created/updated)
     */
    public function import($file, int $rkapId, int $divisionId): int
    {
        $rows = Excel::toArray(new Form1Import(), $file);
        $sheet = $rows[0] ?? [];

        $dataRows = collect($sheet)
            ->skip(1)
            ->filter(fn ($row) => $this->rowIsFilled($row))
            ->values();

        if ($dataRows->isEmpty()) {
            throw ValidationException::withMessages([
                'file' => 'File tidak mengandung data yang dapat diimpor.',
            ]);
        }

        $count = 0;

        DB::transaction(function () use ($dataRows, $rkapId, $divisionId, &$count) {
            foreach ($dataRows as $row) {
                $this->importRow($row, $rkapId, $divisionId);
                $count++;
            }
        });

        return $count;
    }

    /**
     * Flatten a collection of risks (with relationships loaded) to export rows.
     */
    public function toExportRows(Collection $risks): Collection
    {
        return $risks->map(function (RiskIdentification $risk, $index) {
            $analysis = $risk->analysis->first();
            $strategy = $risk->departmentRiskStrategies->first();

            return [
                'no' => $index + 1,
                'company_target' => $risk->departmentTarget?->companyTarget?->target ?? '-',
                'department_target' => $risk->departmentTarget?->target ?? '-',
                'rating' => $risk->departmentTarget?->ratingCriteria?->rating ?? '-',
                'risk' => $risk->risk,
                'risk_direction' => $risk->risk_direction === 'positive' ? 'Positif' : 'Negatif',
                'risk_type' => $risk->riskType?->name ?? '-',
                'risk_taxonomy' => $risk->riskTaxonomy?->name ?? '-',
                'reasons' => $risk->reasons->pluck('reason')->implode('; '),
                'impacts' => $risk->impacts->pluck('impact')->implode('; '),
                'probability' => $analysis?->riskProbability?->point ?? '-',
                'impact' => $analysis?->riskImpact?->point ?? '-',
                'score' => $analysis?->riskScoreValue?->score ?? ($analysis ? (int) $analysis->riskProbability?->point * (int) $analysis->riskImpact?->point : '-'),
                'level' => $analysis?->riskScoreValue?->level
                    ? $this->levelShort($analysis->riskScoreValue->level)
                    : '-',
                'strategy' => $this->strategyLabel($strategy?->strategy),
                'work_program' => $risk->workPrograms->first()->name ?? '-',
            ];
        })->values();
    }

    public function exportRow(RiskIdentification $risk, int $index): array
    {
        return $this->toExportRows(collect([$risk]))->first();
    }

    protected function importRow(array $row, int $rkapId, int $divisionId): void
    {
        $line = (int) ($row[0] ?? ($row['no'] ?? 0));

        $companyTarget = CompanyTarget::firstOrCreate(
            ['target' => $this->text($row, 1, 'company_target', 'Sasaran Perusahaan'), 'erkap_rkap_id' => $rkapId],
            ['target' => $this->text($row, 1, 'company_target', 'Sasaran Perusahaan')]
        );

        $ratingCode = $this->text($row, 3, 'rating', 'Rating');
        $ratingCriteria = RatingCriteria::where('rating', $ratingCode)->first();

        if (! $ratingCriteria) {
            $this->fail(
                "Rating '{$ratingCode}' tidak dikenal pada baris {$line}. Nilai yang valid: ".implode(', ', ErkapRatingLevel::values()).'.',
                'rating'
            );
        }

        $departmentTarget = DepartmentTarget::firstOrCreate(
            [
                'target' => $this->text($row, 2, 'department_target', 'Sasaran Satuan Kerja'),
                'division_id' => $divisionId,
                'erkap_company_target_id' => $companyTarget->id,
                'erkap_rating_criteria_id' => $ratingCriteria->id,
            ],
            [
                'target' => $this->text($row, 2, 'department_target', 'Sasaran Satuan Kerja'),
                'division_id' => $divisionId,
                'erkap_company_target_id' => $companyTarget->id,
                'erkap_rating_criteria_id' => $ratingCriteria->id,
            ]
        );

        $riskTaxonomy = $this->resolveRiskTaxonomy($this->text($row, 7, 'risk_taxonomy', 'Taksonomi'), $line);
        $riskType = $this->resolveRiskType($this->text($row, 6, 'risk_type', 'Tipe Risiko'), $riskTaxonomy->id, $line);

        $risk = RiskIdentification::firstOrCreate(
            ['risk' => $this->text($row, 4, 'risk', 'Identifikasi Risiko')],
            [
                'erkap_department_target_id' => $departmentTarget->id,
                'erkap_risk_type_id' => $riskType->id,
                'erkap_risk_taxonomy_id' => $riskTaxonomy->id,
                'risk_direction' => $this->resolveDirection($this->text($row, 5, 'risk_direction', 'Positif/Negatif')),
            ]
        );

        if ($risk->erkap_department_target_id !== $departmentTarget->id) {
            $risk->update([
                'erkap_department_target_id' => $departmentTarget->id,
                'erkap_risk_type_id' => $riskType->id,
                'erkap_risk_taxonomy_id' => $riskTaxonomy->id,
                'risk_direction' => $this->resolveDirection($this->text($row, 5, 'risk_direction', 'Positif/Negatif')),
            ]);
        }

        foreach ($this->splitList($this->text($row, 8, 'reasons', 'Penyebab')) as $reason) {
            RiskIdentificationReason::firstOrCreate(
                ['erkap_risk_identification_id' => $risk->id, 'reason' => $reason],
                ['reason' => $reason]
            );
        }

        foreach ($this->splitList($this->text($row, 9, 'impacts', 'Dampak')) as $impact) {
            RiskIdentificationImpact::firstOrCreate(
                ['erkap_risk_identification_id' => $risk->id, 'impact' => $impact],
                ['impact' => $impact]
            );
        }

        $analysis = $this->resolveRiskAnalysis($risk, $row, $line);

        $strategyValue = $this->resolveStrategy($this->text($row, 14, 'strategy', 'Strategi'), $line);
        DepartmentRiskStrategy::firstOrCreate(
            ['erkap_risk_identification_id' => $risk->id],
            ['strategy' => $strategyValue]
        );

        $programName = $this->text($row, 15, 'work_program', 'Program Kerja');

        try {
            WorkProgram::create([
                'erkap_risk_identification_id' => $risk->id,
                'name' => $programName,
                'units' => '-',
                'status' => 'draft',
            ]);
        } catch (ValidationException $e) {
            $this->fail(
                'Baris '.$line.' (Program: '.$programName.'): '.$e->validator->errors()->first(),
                'work_program'
            );
        }
    }

    protected function resolveRiskAnalysis(RiskIdentification $risk, array $row, int $line): ?RiskAnalysis
    {
        $probabilityPoint = $this->numeric($row, 10, 'probability', 'Probabilitas', $line);
        $impactPoint = $this->numeric($row, 11, 'impact', 'Dampak', $line);

        if ($probabilityPoint === null || $impactPoint === null) {
            return null;
        }

        $probability = RiskProbability::where('point', $probabilityPoint)->first();
        $impact = RiskImpact::where('point', $impactPoint)->first();

        if (! $probability) {
            $this->fail("Probabilitas {$probabilityPoint} tidak ditemukan pada baris {$line}.", 'probability');
        }

        if (! $impact) {
            $this->fail("Dampak {$impactPoint} tidak ditemukan pada baris {$line}.", 'impact');
        }

        $scoreLevel = RiskScoreLevel::where('erkap_risk_probability_id', $probability->id)
            ->where('erkap_risk_impact_id', $impact->id)
            ->first();

        return RiskAnalysis::updateOrCreate(
            ['erkap_risk_identification_id' => $risk->id],
            [
                'erkap_risk_probability_id' => $probability->id,
                'erkap_risk_impact_id' => $impact->id,
                'erkap_risk_score_value_id' => $scoreLevel?->id,
            ]
        );
    }

    protected function resolveRiskType(string $name, int $taxonomyId, int $line): RiskType
    {
        if ($name === '' || $name === '-') {
            $this->fail('Tipe Risiko wajib diisi pada baris '.$line.'.', 'risk_type');
        }

        $riskType = RiskType::where('name', $name)->first();

        if ($riskType) {
            return $riskType;
        }

        return RiskType::create([
            'name' => $name,
            'risk_taxonomy_id' => $taxonomyId,
        ]);
    }

    protected function resolveRiskTaxonomy(string $name, int $line): RiskTaxonomy
    {
        if ($name === '' || $name === '-') {
            $this->fail('Taksonomi wajib diisi pada baris '.$line.'.', 'risk_taxonomy');
        }

        $taxonomy = RiskTaxonomy::where('name', $name)->first();

        if ($taxonomy) {
            return $taxonomy;
        }

        $riskAppetiteId = RiskAppetite::orderBy('id')->value('id');

        if (! $riskAppetiteId) {
            $riskAppetiteId = RiskAppetite::create(['name' => 'Default'])->id;
        }

        return RiskTaxonomy::create([
            'name' => $name,
            'risk_appetite_id' => $riskAppetiteId,
        ]);
    }

    protected function resolveDirection(string $value): string
    {
        $key = strtolower(trim($value));

        return self::DIRECTION_MAP[$key] ?? 'negative';
    }

    protected function resolveStrategy(string $value, int $line): string
    {
        $key = strtolower(trim($value));

        if (! isset(self::STRATEGY_MAP[$key])) {
            $this->fail("Strategi '{$value}' tidak dikenal pada baris {$line}.", 'strategy');
        }

        return self::STRATEGY_MAP[$key];
    }

    protected function strategyLabel(?string $strategy): string
    {
        return $strategy
            ? (ErkapRiskTreatmentType::fromLegacy($strategy)?->label() ?? $strategy)
            : '-';
    }

    protected function splitList(string $value): array
    {
        if ($value === '' || $value === '-') {
            return [];
        }

        return collect(preg_split('/[;\n\r]+|\|/', $value))
            ->map(fn ($item) => trim($item))
            ->filter(fn ($item) => $item !== '')
            ->values()
            ->all();
    }

    protected function text(array $row, int $index, string $key, string $label): string
    {
        $value = $row[$index] ?? ($row[$key] ?? '');

        return $value === null ? '' : trim((string) $value);
    }

    protected function numeric(array $row, int $index, string $key, string $label, int $line): ?int
    {
        $value = $row[$index] ?? ($row[$key] ?? null);

        if ($value === null || $value === '' || $value === '-') {
            return null;
        }

        return (int) $value;
    }

    protected function rowIsFilled(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && $cell !== '' && $cell !== '-') {
                return true;
            }
        }

        return false;
    }

    protected function fail(string $message, string $field): void
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}