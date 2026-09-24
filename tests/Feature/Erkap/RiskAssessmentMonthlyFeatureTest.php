<?php

namespace Tests\Feature\Erkap;

use App\Models\Erkap\RiskAppetite;
use App\Models\Erkap\RiskAssessmentMonthly;
use App\Models\Erkap\RiskIdentification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ActsAsSuperAdmin;
use Tests\TestCase;

class RiskAssessmentMonthlyFeatureTest extends TestCase
{
    use RefreshDatabase, ActsAsSuperAdmin;

    private RiskIdentification $risk;
    private RiskAppetite $appetite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSuperAdmin();
        $this->risk = RiskIdentification::factory()->create();
        $this->appetite = RiskAppetite::factory()->create();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'erkap_risk_identification_id' => $this->risk->id,
            'month' => 9,
            'year' => 2026,
            'inherent_probability' => 4,
            'inherent_impact' => 5,
            'current_probability' => 3,
            'current_impact' => 4,
            'residual_probability' => 2,
            'residual_impact' => 2,
            'mitigation_plan' => 'Meningkatkan frekuensi review.',
            'mitigation_status' => 'on_progress',
            'risk_owner' => 'Kepala Divisi',
            'target_date' => '2026-10-01',
            'risk_appetite_id' => $this->appetite->id,
        ], $overrides);
    }

    private function businessProcessesPayload(int $count): array
    {
        $items = [];
        for ($i = 1; $i <= $count; $i++) {
            $items[] = [
                'process_name' => "Proses bisnis $i",
                'description' => "Deskripsi $i",
                'owner' => "Pemilik $i",
                'risk_level' => $i % 2 === 0 ? 'high' : 'medium',
            ];
        }

        return ['business_processes' => $items];
    }

    public function test_create_page_lists_risk_identifications_and_appetites(): void
    {
        $response = $this->get(route('erkap.risk-assessments-monthly.create'));

        $response->assertOk();
        $response->assertViewHas('riskIdentifications');
        $response->assertViewHas('riskAppetites', function ($appetites) {
            return $appetites->contains('id', $this->appetite->id);
        });
    }

    public function test_store_creates_assessment_with_business_processes_and_scores(): void
    {
        $response = $this->post(
            route('erkap.risk-assessments-monthly.store'),
            $this->validPayload() + $this->businessProcessesPayload(2)
        );

        $response->assertRedirect(route('erkap.risk-assessments-monthly.index'));

        $this->assertDatabaseHas('erkap_risk_assessments_monthly', [
            'erkap_risk_identification_id' => $this->risk->id,
            'month' => 9,
            'year' => 2026,
            'inherent_score' => 20,
            'current_score' => 12,
            'residual_score' => 4,
            'risk_appetite_id' => $this->appetite->id,
            'target_date' => '2026-10-01',
        ]);

        $assessment = RiskAssessmentMonthly::where('erkap_risk_identification_id', $this->risk->id)->firstOrFail();
        $this->assertSame(2, $assessment->businessProcesses()->count());
        $this->assertSame('Proses bisnis 1', $assessment->businessProcesses()->first()->process_name);
    }

    public function test_update_replaces_business_processes(): void
    {
        $this->post(
            route('erkap.risk-assessments-monthly.store'),
            $this->validPayload() + $this->businessProcessesPayload(2)
        );

        $assessment = RiskAssessmentMonthly::where('erkap_risk_identification_id', $this->risk->id)->firstOrFail();

        $response = $this->put(
            route('erkap.risk-assessments-monthly.update', $assessment->id),
            $this->validPayload(['mitigation_status' => 'done']) + $this->businessProcessesPayload(1)
        );

        $response->assertRedirect(route('erkap.risk-assessments-monthly.index'));

        $assessment->refresh();
        $this->assertSame('done', $assessment->mitigation_status);
        $this->assertSame(1, $assessment->businessProcesses()->count());
        $this->assertSame('Proses bisnis 1', $assessment->businessProcesses()->first()->process_name);
    }

    public function test_store_validates_missing_inherent_fields(): void
    {
        $payload = $this->validPayload(['inherent_probability' => null]);

        $response = $this->from(route('erkap.risk-assessments-monthly.create'))
            ->post(route('erkap.risk-assessments-monthly.store'), $payload);

        $response->assertSessionHasErrors('inherent_probability');
        $this->assertDatabaseCount('erkap_risk_assessments_monthly', 0);
    }

    public function test_index_shows_monthly_assessments_with_filters(): void
    {
        $this->post(
            route('erkap.risk-assessments-monthly.store'),
            $this->validPayload() + $this->businessProcessesPayload(1)
        );

        $response = $this->get(route('erkap.risk-assessments-monthly.index', ['year' => 2026, 'month' => 9]));

        $response->assertOk();
        $response->assertSee('Proses bisnis 1');
        $this->assertSame(1, $response->viewData('assessments')->total());
    }
}