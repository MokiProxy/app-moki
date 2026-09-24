<?php

namespace Tests\Unit\Erkap;

use App\Models\Erkap\RiskAssessmentMonthly;
use App\Models\Erkap\RiskBusinessProcess;
use App\Models\Erkap\RiskIdentification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskAssessmentMonthlyTest extends TestCase
{
    use RefreshDatabase;

    private function createAssessment(array $overrides = []): RiskAssessmentMonthly
    {
        return RiskAssessmentMonthly::create(array_merge([
            'erkap_risk_identification_id' => RiskIdentification::factory()->create()->id,
            'month' => 9,
            'year' => 2026,
            'inherent_probability' => 4,
            'inherent_impact' => 5,
            'mitigation_status' => 'on_progress',
        ], $overrides));
    }

    public function test_calculates_scores_on_save(): void
    {
        $assessment = $this->createAssessment();

        $this->assertSame(20, $assessment->inherent_score);
        $this->assertNull($assessment->current_score);
        $this->assertNull($assessment->residual_score);

        $assessment->update([
            'current_probability' => 3,
            'current_impact' => 4,
            'residual_probability' => 2,
            'residual_impact' => 2,
        ]);

        $this->assertSame(12, $assessment->current_score);
        $this->assertSame(4, $assessment->residual_score);
    }

    public function test_marks_overdue_when_target_date_past_and_not_done(): void
    {
        $assessment = $this->createAssessment(['target_date' => now()->subDay()]);

        $this->assertTrue($assessment->markOverdueIfDue());
        $this->assertSame('overdue', $assessment->mitigation_status);
    }

    public function test_does_not_mark_overdue_when_status_done(): void
    {
        $assessment = $this->createAssessment([
            'target_date' => now()->subDay(),
            'mitigation_status' => 'done',
        ]);

        $this->assertFalse($assessment->markOverdueIfDue());
        $this->assertSame('done', $assessment->mitigation_status);
    }

    public function test_does_not_mark_overdue_without_target_date(): void
    {
        $assessment = $this->createAssessment();

        $this->assertFalse($assessment->markOverdueIfDue());
        $this->assertSame('on_progress', $assessment->mitigation_status);
    }

    public function test_does_not_mark_overdue_when_target_date_in_future(): void
    {
        $assessment = $this->createAssessment(['target_date' => now()->addWeek()]);

        $this->assertFalse($assessment->markOverdueIfDue());
        $this->assertSame('on_progress', $assessment->mitigation_status);
    }

    public function test_mark_all_overdue_counts_updated_assessments(): void
    {
        $this->createAssessment(['target_date' => now()->subDay()]);
        $this->createAssessment(['target_date' => now()->subDay(), 'mitigation_status' => 'done']);
        $this->createAssessment(['target_date' => now()->subDay()]);

        $this->assertSame(2, RiskAssessmentMonthly::markAllOverdueIfDue());
        $this->assertSame(
            2,
            RiskAssessmentMonthly::where('mitigation_status', 'overdue')->count()
        );
    }

    public function test_business_processes_relation(): void
    {
        $assessment = $this->createAssessment();

        RiskBusinessProcess::create([
            'risk_assessment_monthly_id' => $assessment->id,
            'process_name' => 'Pengadaan barang',
            'owner' => 'Divisi IT',
            'risk_level' => 'high',
        ]);

        $this->assertSame(1, $assessment->businessProcesses()->count());
        $this->assertSame('Pengadaan barang', $assessment->businessProcesses->first()->process_name);
    }
}