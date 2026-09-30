<?php

namespace Tests\Feature\Erkap;

use App\Exports\Erkap\WorkProgramExport;
use App\Models\Employee;
use App\Models\Regional;
use App\Models\User;
use App\Support\ErrorMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\ActsAsSuperAdmin;
use Tests\Concerns\BuildsErkapChain;
use Tests\TestCase;

class WorkProgramFeatureTest extends TestCase
{
    use ActsAsSuperAdmin, BuildsErkapChain, RefreshDatabase;

    private array $chain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSuperAdmin();
        $this->chain = $this->buildErkapChain();
    }

    public function test_index_displays_percentage_plan_columns(): void
    {
        $this->get(route('erkap.work-programs.index'))
            ->assertOk()
            ->assertSee('Jan (%)')
            ->assertSee('Des (%)')
            ->assertSee('8,00%')
            ->assertSee('100,00%')
            ->assertDontSee('8,33%')
            ->assertDontSee('Kum Jan')
            ->assertDontSee('Kum Des');
    }

    public function test_index_handles_zero_year_plan(): void
    {
        $this->chain['workProgram']->update([
            'year_plan' => 0,
            'jan_plan' => 0,
            'feb_plan' => 0,
            'mar_plan' => 0,
            'apr_plan' => 0,
            'may_plan' => 0,
            'jun_plan' => 0,
            'jul_plan' => 0,
            'aug_plan' => 0,
            'sep_plan' => 0,
            'oct_plan' => 0,
            'nov_plan' => 0,
            'dec_plan' => 0,
        ]);

        $this->get(route('erkap.work-programs.index'))
            ->assertOk()
            ->assertSee('0,00%');
    }

    public function test_excel_export_contains_percentage_plan_columns_without_cumulative_columns(): void
    {
        Excel::fake();
        Excel::matchByRegex();

        $this->get(route('erkap.work-programs.export'))
            ->assertOk();

        Excel::assertDownloaded('/^program-kerja-.*\.xlsx$/', function (WorkProgramExport $export) {
            $headings = $export->headings();

            return in_array('Jan (%)', $headings, true)
                && in_array('Des (%)', $headings, true)
                && ! in_array('Kum Jan %', $headings, true)
                && ! in_array('Kum Des %', $headings, true)
                && count($headings) === 20;
        });
    }

    public function test_pdf_export_renders_percentage_plan(): void
    {
        $this->get(route('erkap.work-programs.export-pdf'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_store_rejects_percentages_above_one_hundred(): void
    {
        $this->post(route('erkap.work-programs.store'), $this->validWorkProgramPayload([
            'year_plan' => 101,
            'jan_plan' => 101,
            'dec_plan' => 3,
        ]))->assertSessionHasErrors(['year_plan', 'jan_plan']);

        $this->assertDatabaseCount('erkap_work_programs', 1);
    }

    public function test_store_rejects_monthly_total_that_does_not_match_year_plan(): void
    {
        $this->post(route('erkap.work-programs.store'), $this->validWorkProgramPayload([
            'dec_plan' => 13,
        ]))->assertSessionHasErrors('year_plan');

        $this->assertDatabaseCount('erkap_work_programs', 1);
    }

    public function test_update_rejects_monthly_total_that_does_not_match_year_plan(): void
    {
        $workProgram = $this->chain['workProgram'];

        $this->put(route('erkap.work-programs.update', $workProgram), $this->validWorkProgramPayload([
            'dec_plan' => 13,
        ]))->assertSessionHasErrors('year_plan');

        $this->assertSame(100.0, (float) $workProgram->fresh()->year_plan);
        $this->assertSame(12.0, (float) $workProgram->fresh()->dec_plan);
    }

    public function test_update_surfaces_lock_reason_instead_of_generic_validation_message(): void
    {
        $workProgram = $this->chain['workProgram'];
        $this->chain['risk']->update(['status' => 'approved']);

        $employee = Employee::create([
            'employee_id' => 'EMP-WP-'.uniqid(),
            'name' => 'Cost Owner',
            'division_id' => $this->chain['division']->id,
            'regional_id' => Regional::factory()->create()->id,
        ]);

        $user = User::factory()->create(['employee_id' => $employee->employee_id]);
        $user->assignRole(Role::firstOrCreate(['name' => 'erkap-cost-owner', 'guard_name' => 'web']));
        foreach (['erkap.work-programs.view', 'erkap.work-programs.edit'] as $name) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }

        $this->actingAs($user)
            ->from(route('erkap.work-programs.edit', $workProgram))
            ->put(route('erkap.work-programs.update', $workProgram), $this->validWorkProgramPayload([
                'name' => 'Program Kerja Direvisi',
            ]))
            ->assertRedirect(route('erkap.work-programs.edit', $workProgram))
            ->assertSessionHas('error', fn (?string $message): bool => is_string($message)
                && str_contains($message, 'tidak dapat diubah')
                && ! str_contains($message, 'The given data was invalid'));

        $this->assertSame('Program Kerja Uji', $workProgram->fresh()->name);
    }

    public function test_error_message_helper_reads_validation_errors_not_generic_text(): void
    {
        $this->assertSame('Total bulanan harus sama dengan target tahunan persentase.', ErrorMessage::from(
            ValidationException::withMessages([
                'monthly_breakdown' => 'Total bulanan harus sama dengan target tahunan persentase.',
            ])
        ));

        $this->assertSame('a; b', ErrorMessage::from(ValidationException::withMessages([
            'year_plan' => ['a', 'b'],
        ])));

        $this->assertSame(
            'Anda tidak memiliki izin untuk mengakses data ini.',
            ErrorMessage::from(new HttpException(403))
        );
    }

    private function validWorkProgramPayload(array $overrides = []): array
    {
        return array_merge([
            'erkap_risk_identification_id' => $this->chain['risk']->id,
            'name' => 'Program Kerja Persentase',
            'units' => 'Unit',
            'year_plan' => 100,
            'jan_plan' => 8,
            'feb_plan' => 8,
            'mar_plan' => 8,
            'apr_plan' => 8,
            'may_plan' => 8,
            'jun_plan' => 8,
            'jul_plan' => 8,
            'aug_plan' => 8,
            'sep_plan' => 8,
            'oct_plan' => 8,
            'nov_plan' => 8,
            'dec_plan' => 12,
        ], $overrides);
    }
}