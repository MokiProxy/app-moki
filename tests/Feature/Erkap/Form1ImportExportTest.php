<?php

namespace Tests\Feature\Erkap;

use App\Models\Division;
use App\Models\Erkap\CompanyTarget;
use App\Models\Erkap\DepartmentRiskStrategy;
use App\Models\Erkap\DepartmentTarget;
use App\Models\Erkap\RatingCriteria;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RiskImpact;
use App\Models\Erkap\RiskProbability;
use App\Models\Erkap\RiskScoreLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class Form1ImportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rkap = RKAP::factory()->create(['year' => 2026]);
        $this->division = Division::factory()->create();
        $this->ratingA = RatingCriteria::factory()->create(['rating' => 'A', 'qualification' => 'Sangat Baik']);
        $this->ratingC = RatingCriteria::factory()->create(['rating' => 'BBB', 'qualification' => 'Penting']);

        $this->prob3 = RiskProbability::create(['name' => 'Mungkin', 'point' => 3]);
        $this->prob4 = RiskProbability::create(['name' => 'Sering', 'point' => 4]);
        $this->impact2 = RiskImpact::create(['name' => 'Sedang', 'point' => 2]);
        $this->impact4 = RiskImpact::create(['name' => 'Besar', 'point' => 4]);

        RiskScoreLevel::create([
            'erkap_risk_probability_id' => $this->prob3->id,
            'erkap_risk_impact_id' => $this->impact4->id,
            'score' => 12,
            'level' => 'High',
        ]);
    }

    protected function service(): \App\Services\Form1ImportExportService
    {
        return app(\App\Services\Form1ImportExportService::class);
    }

    protected function makeCsv(string $body, string $name = 'form1.csv'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'form1');
        file_put_contents($path, $body);

        return new UploadedFile($path, $name, 'text/csv', null, true);
    }

    protected function importCsv(RKAP $rkap, Division $division, array $rows): int
    {
        $lines = array_merge(
            [implode(',', $this->service()::HEADINGS)],
            collect($rows)
                ->map(fn (array $row) => implode(',', array_map('strval', $row)))
                ->all(),
        );

        return $this->service()->import($this->makeCsv(implode("\n", $lines)), $rkap->id, $division->id);
    }

    public function test_import_creates_full_risk_chain_from_flat_rows(): void
    {
        $this->importCsv($this->rkap, $this->division, [
            ['1', 'Meningkatkan profit', 'Meningkatkan efisiensi', 'A', 'Gangguan sistem TI', 'Negatif', 'Operasional', 'Teknologi Informasi', 'Kegagalan sistem; Kesalahan manusia', 'Kehilangan data; Penundaan pelaporan', '3', '4', '12', 'H', 'Kurangi', 'Program pemeliharaan'],
        ]);

        $risk = \App\Models\Erkap\RiskIdentification::where('risk', 'Gangguan sistem TI')->first();
        $this->assertNotNull($risk);

        $deptTarget = $risk->departmentTarget;
        $this->assertSame('Meningkatkan efisiensi', $deptTarget->target);
        $this->assertSame($this->division->id, $deptTarget->division_id);
        $this->assertSame($this->ratingA->id, $deptTarget->erkap_rating_criteria_id);
        $this->assertSame('Meningkatkan profit', $deptTarget->companyTarget->target);
        $this->assertSame($this->rkap->id, $deptTarget->companyTarget->erkap_rkap_id);

        $this->assertSame('negative', $risk->risk_direction);
        $this->assertSame('Operasional', $risk->riskType->name);
        $this->assertSame('Teknologi Informasi', $risk->riskTaxonomy->name);
        $this->assertSame(['Kegagalan sistem', 'Kesalahan manusia'], $risk->reasons->pluck('reason')->all());
        $this->assertSame(['Kehilangan data', 'Penundaan pelaporan'], $risk->impacts->pluck('impact')->all());

        $analysis = $risk->analysis->first();
        $this->assertSame($this->prob3->id, $analysis->erkap_risk_probability_id);
        $this->assertSame($this->impact4->id, $analysis->erkap_risk_impact_id);
        $this->assertSame(12, $analysis->riskScoreValue->score);
        $this->assertSame('High', $analysis->riskScoreValue->level);

        $this->assertSame('reduction', $risk->departmentRiskStrategies->first()->strategy);
        $this->assertSame('Program pemeliharaan', $risk->workPrograms->first()->name);
    }

    public function test_import_is_transactional_on_business_rule_violation(): void
    {
        $this->expectException(ValidationException::class);

        try {
            $this->importCsv($this->rkap, $this->division, [
                ['1', 'Target A', 'Sasaran A', 'BBB', 'Risiko di sasaran C', 'Negatif', 'Ops', 'TI', 'Penyebab', 'Dampak', '3', '4', '12', 'H', 'Kurangi', 'Program gagal'],
                ['2', 'Target B', 'Sasaran B', 'A', 'Risiko valid', 'Negatif', 'Ops', 'TI', 'Penyebab', 'Dampak', '3', '4', '12', 'H', 'Kurangi', 'Program sukses'],
            ]);
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Rating A ke atas', $e->validator->errors()->first());
            throw $e;
        }
    }

    public function test_import_rolls_back_everything_on_failure(): void
    {
        $this->expectException(ValidationException::class);

        try {
            $this->importCsv($this->rkap, $this->division, [
                ['1', 'Target A', 'Sasaran A', 'A', 'Risiko satu', 'Negatif', 'Ops', 'TI', 'Penyebab', 'Dampak', '3', '4', '12', 'H', 'Kurangi', 'Program satu'],
                ['2', 'Target B', 'Sasaran B', 'BBB', 'Risiko dua', 'Negatif', 'Ops', 'TI', 'Penyebab', 'Dampak', '3', '4', '12', 'H', 'Kurangi', 'Program gagal'],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        $this->fail('Expected exception not thrown');
    }

    public function test_validate_rollback_after_failed_import(): void
    {
        $imported = $this->importCsv($this->rkap, $this->division, [
            ['1', 'Target A', 'Sasaran A', 'A', 'Risiko satu', 'Negatif', 'Ops', 'TI', 'Penyebab', 'Dampak', '3', '4', '12', 'H', 'Kurangi', 'Program satu'],
        ]);

        $this->assertSame(1, $imported);
        $this->assertSame(1, \App\Models\Erkap\RiskIdentification::count());

        try {
            $this->importCsv($this->rkap, $this->division, [
                ['1', 'Target B', 'Sasaran B', 'BBB', 'Risiko dua', 'Negatif', 'Ops', 'TI', 'Penyebab', 'Dampak', '3', '4', '12', 'H', 'Kurangi', 'Program gagal'],
            ]);
        } catch (ValidationException $e) {
            // ignored
        }

        $this->assertSame(1, \App\Models\Erkap\RiskIdentification::count());
        $this->assertSame(1, DepartmentTarget::count());
        $this->assertSame(1, CompanyTarget::count());
    }

    public function test_export_round_trips_after_import(): void
    {
        $this->importCsv($this->rkap, $this->division, [
            ['1', 'Meningkatkan profit', 'Meningkatkan efisiensi', 'A', 'Gangguan sistem TI', 'Negatif', 'Operasional', 'Teknologi Informasi', 'Kegagalan sistem; Kesalahan manusia', 'Kehilangan data; Penundaan pelaporan', '3', '4', '12', 'H', 'Kurangi', 'Program pemeliharaan'],
        ]);

        $risk = \App\Models\Erkap\RiskIdentification::with([
            'departmentTarget.companyTarget',
            'departmentTarget.ratingCriteria',
            'riskType',
            'riskTaxonomy',
            'reasons',
            'impacts',
            'analysis.riskProbability',
            'analysis.riskImpact',
            'analysis.riskScoreValue',
            'departmentRiskStrategies',
            'workPrograms',
        ])->with(['departmentTarget.companyTarget' => fn ($q) => $q->where('erkap_rkap_id', $this->rkap->id)])->get();

        $rows = $this->service()->toExportRows($risk);
        $row = $rows->first();

        $this->assertSame('Meningkatkan profit', $row['company_target']);
        $this->assertSame('Meningkatkan efisiensi', $row['department_target']);
        $this->assertSame('A', $row['rating']);
        $this->assertSame('Gangguan sistem TI', $row['risk']);
        $this->assertSame('Negatif', $row['risk_direction']);
        $this->assertSame('Kegagalan sistem; Kesalahan manusia', $row['reasons']);
        $this->assertSame('H', $row['level']);
        $this->assertSame(12, (int) $row['score']);
        $this->assertSame('Kurangi', $row['strategy']);
        $this->assertSame('Program pemeliharaan', $row['work_program']);
    }

    public function test_import_rejects_empty_file(): void
    {
        $this->expectException(ValidationException::class);

        $this->importCsv($this->rkap, $this->division, [
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
        ]);
    }

    public function test_template_and_export_routes_respond(): void
    {
        $this->importCsv($this->rkap, $this->division, [
            ['1', 'Target A', 'Sasaran A', 'A', 'Risiko satu', 'Negatif', 'Ops', 'TI', 'Penyebab', 'Dampak', '3', '4', '12', 'H', 'Kurangi', 'Program satu'],
        ]);

        $user = \App\Models\User::factory()->create();
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user->assignRole($role);
        $this->actingAs($user);

        Excel::fake();

        $response = $this->get(route('erkap.form1.template'));
        $response->assertStatus(200);

        $response = $this->get(route('erkap.form1.export', [
            'erkap_rkap_id' => $this->rkap->id,
        ]));
        $response->assertStatus(200);
    }

    public function test_template_writes_real_file_with_dropdown_validations(): void
    {
        $path = 'tmp/form1-template-regression-' . uniqid() . '.xlsx';

        try {
            Excel::store(new \App\Exports\Erkap\Form1TemplateExport(), $path, 'local');

            $sheet = (new \PhpOffice\PhpSpreadsheet\Reader\Xlsx())
                ->load(storage_path('app/private/' . $path))
                ->getActiveSheet();

            $validations = $sheet->getDataValidationCollection();

            $expected = ['D' => 'A', 'F' => 'Positif', 'K' => '3', 'L' => '4', 'N' => 'H', 'O' => 'Kurangi'];
            foreach ($expected as $col => $contains) {
                $this->assertArrayHasKey("{$col}2", $validations);
                $this->assertArrayHasKey("{$col}500", $validations);
                $this->assertSame('list', $validations["{$col}2"]->getType());
                $this->assertStringContainsString($contains, $validations["{$col}2"]->getFormula1());
            }

            $this->assertSame('Rating', $sheet->getCell('D1')->getValue());
            $this->assertSame('A', $sheet->getCell('D2')->getValue());
        } finally {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($path);
            rmdir(storage_path('app/private/tmp'));
        }
    }
}