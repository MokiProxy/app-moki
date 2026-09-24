<?php

namespace Tests\Feature\Erkap;

use App\Models\Erkap\ReportItem;
use App\Models\Erkap\RKAP;
use App\Services\Reporting\ReportGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsErkapChain;
use Tests\Concerns\ActsAsSuperAdmin;
use Tests\TestCase;

class ScheduledReportsTest extends TestCase
{
    use RefreshDatabase, ActsAsSuperAdmin, BuildsErkapChain;

    public function test_command_generates_due_reports_on_first_of_month(): void
    {
        $chain = $this->buildErkapChain(['year' => 2026]);

        $this->app->instance(ReportGenerator::class, new class extends ReportGenerator
        {
            public function store(string $reportType, array $params, string $format, ?string $filename = null): string
            {
                return "reports/fake_{$reportType}_{$format}.pdf";
            }
        });

        $this->travelTo(\Illuminate\Support\Carbon::create(2026, 1, 1, 2, 0));

        $this->artisan('erkap:generate-scheduled-reports')
            ->assertExitCode(0);

        $this->assertDatabaseCount('erkap_report_items', 3);

        $this->assertDatabaseHas('erkap_report_items', [
            'report_type' => 'realization',
            'frequency' => 'monthly',
            'status' => 'generated',
        ]);
        $this->assertDatabaseHas('erkap_report_items', [
            'report_type' => 'risk',
            'frequency' => 'monthly',
            'format' => 'excel',
            'status' => 'generated',
        ]);
        $this->assertDatabaseHas('erkap_report_items', [
            'report_type' => 'performance',
            'frequency' => 'quarterly',
            'status' => 'generated',
        ]);

        $this->travelBack();
    }

    public function test_command_captures_failure_as_failed_report_item(): void
    {
        $this->buildErkapChain(['year' => 2026]);

        $this->app->instance(ReportGenerator::class, new class extends ReportGenerator
        {
            public function store(string $reportType, array $params, string $format, ?string $filename = null): string
            {
                throw new \RuntimeException('Disk penuh');
            }
        });

        $this->travelTo(\Illuminate\Support\Carbon::create(2026, 1, 1, 2, 0));

        $this->artisan('erkap:generate-scheduled-reports')
            ->assertExitCode(0);

        $this->assertDatabaseHas('erkap_report_items', [
            'report_type' => 'realization',
            'frequency' => 'monthly',
            'status' => 'failed',
            'error' => 'Disk penuh',
        ]);

        $this->travelBack();
    }
}