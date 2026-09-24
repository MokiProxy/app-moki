<?php

namespace Tests\Unit\Erkap;

use App\Enums\ErkapRiskTreatmentType;
use App\Models\Erkap\DepartmentRiskStrategy;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\RiskTreatment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskTreatmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_risk_treatment(): void
    {
        $treatment = RiskTreatment::factory()->create([
            'treatment_type' => 'reduction',
            'description' => 'Pasang sistem pengendalian',
            'responsible_party' => 'Manajer Risiko',
            'target_date' => now()->addMonths(3)->format('Y-m-d'),
            'status' => 'planned',
        ]);

        $this->assertNotNull($treatment->id);
        $this->assertSame('reduction', $treatment->fresh()->treatment_type);
    }

    public function test_iso_31000_treatment_types_available(): void
    {
        $this->assertSame(
            ['avoidance', 'reduction', 'sharing', 'acceptance'],
            array_keys(RiskTreatment::getTreatmentTypes())
        );
    }

    public function test_enum_maps_legacy_and_unknown_values(): void
    {
        $this->assertSame('avoidance', ErkapRiskTreatmentType::fromLegacy('avoid')?->value);
        $this->assertSame('avoidance', ErkapRiskTreatmentType::fromLegacy('hindari')?->value);
        $this->assertSame('reduction', ErkapRiskTreatmentType::fromLegacy('reduce')?->value);
        $this->assertSame('reduction', ErkapRiskTreatmentType::fromLegacy('mitigate')?->value);
        $this->assertSame('sharing', ErkapRiskTreatmentType::fromLegacy('transfer')?->value);
        $this->assertSame('sharing', ErkapRiskTreatmentType::fromLegacy('berbagi')?->value);
        $this->assertSame('acceptance', ErkapRiskTreatmentType::fromLegacy('accept')?->value);
        $this->assertSame('acceptance', ErkapRiskTreatmentType::fromLegacy('Terima')?->value);
        $this->assertNull(ErkapRiskTreatmentType::fromLegacy('tidak-dikenal'));
        $this->assertNull(ErkapRiskTreatmentType::fromLegacy(''));
    }

    public function test_department_risk_strategy_uses_enum_labels(): void
    {
        $this->assertSame([
            'avoidance' => 'Hindari',
            'reduction' => 'Kurangi',
            'sharing' => 'Berbagi',
            'acceptance' => 'Terima',
        ], DepartmentRiskStrategy::getStrategies());
    }

    public function test_risk_has_many_risk_treatments(): void
    {
        $risk = RiskIdentification::factory()->create();

        RiskTreatment::factory()->count(2)->create([
            'erkap_risk_identification_id' => $risk->id,
            'erkap_department_risk_strategy_id' => null,
        ]);

        $this->assertCount(2, $risk->riskTreatments);
    }

    public function test_department_risk_strategy_has_many_risk_treatments(): void
    {
        $risk = RiskIdentification::factory()->create();
        $strategy = DepartmentRiskStrategy::factory()->create([
            'erkap_risk_identification_id' => $risk->id,
        ]);

        RiskTreatment::factory()->count(2)->create([
            'erkap_risk_identification_id' => $risk->id,
            'erkap_department_risk_strategy_id' => $strategy->id,
        ]);

        $this->assertCount(2, $strategy->riskTreatments);
    }

    public function test_treatment_can_exist_without_strategy(): void
    {
        $risk = RiskIdentification::factory()->create();

        $treatment = RiskTreatment::factory()->create([
            'erkap_risk_identification_id' => $risk->id,
            'erkap_department_risk_strategy_id' => null,
        ]);

        $this->assertNull($treatment->departmentRiskStrategy);
        $this->assertNotNull($treatment->riskIdentification);
    }

    public function test_deleting_risk_cascades_to_treatments(): void
    {
        $risk = RiskIdentification::factory()->create();
        $treatment = RiskTreatment::factory()->create([
            'erkap_risk_identification_id' => $risk->id,
            'erkap_department_risk_strategy_id' => null,
        ]);

        $risk->delete();

        $this->assertDatabaseMissing('erkap_risk_treatments', ['id' => $treatment->id]);
    }

    public function test_risk_treatment_soft_delete(): void
    {
        $treatment = RiskTreatment::factory()->create();

        $treatment->delete();

        $this->assertSoftDeleted('erkap_risk_treatments', ['id' => $treatment->id]);
        $this->assertNull(RiskTreatment::find($treatment->id));
    }
}