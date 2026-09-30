<?php

namespace Tests\Feature\Erkap;

use App\Models\ChartOfAccount;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Erkap\Activity;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\CostElement;
use App\Models\Erkap\ManagementArea;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\RoutineCost;
use App\Models\Erkap\WorkProgram;
use App\Models\Erkap\ZBBReview;
use App\Models\Regional;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\BudgetOpexConsolidationService;
use App\Services\Erkap\ZBBReviewService;
use App\Support\CoaCode;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsErkapChain;
use Tests\TestCase;

/**
 * F10 — acceptance end-to-end lintas role: input → approval → output.
 *
 * Test lain menguji satu lapisan: form, approval, atau laporan. Yang diuji
 * di sini adalah satu dokumen Biaya Rutin yang sama melewati seluruh rantai,
 * dengan setiap peran memakai role dan permission hasil seeder sungguhan —
 * bukan permission artificial seperti `givePermissionTo()` di dalam test.
 *
 * Jadi bila matriks izin F10 bergeser (mis. `erkap-cost-owner` kehilangan izin
 * baca struktur, atau PPK kehilangan hak approve), test ini gagal pada tahap
 * yang memang tempat alurnya rusak, bukan diam-diam lolos.
 */
class ErkapCrossRoleAcceptanceTest extends TestCase
{
    use BuildsErkapChain, RefreshDatabase;

    private array $chain;

    private RKAP $rkap;

    private CostCenter $costCenter;

    private CostElement $costElement;

    private ChartOfAccount $account;

    private WorkProgram $workProgram;

    private User $costOwner;

    private User $ppk;

    private User $controller;

    private User $accounting;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->seed(RolePermissionSeeder::class);

        $this->chain = $this->buildErkapChain();
        $this->workProgram = $this->chain['workProgram'];

        // `BuildsErkapChain` memakai RKAP berstatus approved, sedangkan
        // `assertNotLocked()` menolak input pada fase finalisasi/approved/
        // archived. Alur ini justru menguji input, jadi periodenya diturunkan
        // ke fase penyusunan.
        $this->rkap = RKAP::findOrFail($this->chain['rkapId']);
        $this->rkap->update(['status' => 'draft', 'phase' => 'preparation']);

        $area = ManagementArea::factory()->create([
            'division_id' => $this->chain['division']->id,
        ]);

        $activity = Activity::factory()->create([
            'erkap_management_area_id' => $area->id,
        ]);

        $this->costCenter = CostCenter::factory()->create([
            'division_id' => $this->chain['division']->id,
            'erkap_business_unit_id' => $area->erkap_business_unit_id,
            'erkap_location_id' => $area->erkap_location_id,
            'erkap_management_area_id' => $area->id,
            'erkap_activity_id' => $activity->id,
        ]);

        $this->costElement = CostElement::factory()->create();
        $this->account = ChartOfAccount::factory()
            ->composed($this->costCenter, $this->costElement)
            ->create(['type' => 'expense']);

        $this->costOwner = $this->userInDivision('erkap-cost-owner', $this->chain['division']);
        $this->ppk = $this->userInDivision('erkap-ppk', $this->chain['division']);
        $this->controller = $this->userInDivision('erkap-controller', $this->chain['division']);
        $this->accounting = $this->userInDivision('erkap-accounting', $this->chain['division']);
    }

    /* ---------------------------------------------------------------------
     | Tahap 1 — input
     | ------------------------------------------------------------------ */

    public function test_cost_owner_records_a_routine_cost_against_the_composed_account(): void
    {
        $this->actingAs($this->costOwner)
            ->post(route('erkap.routine-costs.store'), $this->payload())
            ->assertRedirect(route('erkap.routine-costs.index'))
            ->assertSessionHas('success');

        $routineCost = RoutineCost::firstOrFail();

        // Prinsip K-1: COA diturunkan dari pasangan Pusat Biaya + Elemen Biaya,
        // bukan dipilih bebas. Field `chart_of_account_id` sengaja tidak dikirim
        // supaya test membuktikan controller yang menetapkannya.
        $this->assertSame($this->account->id, $routineCost->chart_of_account_id);
        $this->assertSame($this->costCenter->id, $routineCost->cost_center_id);
        $this->assertSame($this->costElement->id, $routineCost->erkap_cost_element_id);
        $this->assertSame(15, strlen($this->account->code));
        $this->assertSame(11, strlen($this->costCenter->code));
        $this->assertSame('draft', $routineCost->status);
    }

    public function test_a_cost_owner_of_another_division_cannot_record_against_a_foreign_cost_center(): void
    {
        $foreignOwner = $this->userInDivision(
            'erkap-cost-owner',
            Division::factory()->create()
        );

        // Controller menjawab redirect + flash error, bukan 403, karena
        // `assertWorkProgramAccess()` di dalam blok `catch` yang sama.
        // Yang diuji adalah hasil yang penting: baris tidak pernah tersimpan,
        // dan alasannya tidak ikut terbawa ke layar pengguna.
        $this->actingAs($foreignOwner)
            ->post(route('erkap.routine-costs.store'), $this->payload())
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, RoutineCost::count());
    }

    /* ---------------------------------------------------------------------
     | Tahap 2 — approval
     | ------------------------------------------------------------------ */

    public function test_the_document_travels_ppk_then_controller(): void
    {
        $this->storeRoutineCost();
        $routineCost = RoutineCost::firstOrFail();

        $this->actingAs($this->costOwner)
            ->post(route('erkap.routine-costs.submit', $routineCost->id))
            ->assertRedirect(route('erkap.routine-costs.index'))
            ->assertSessionHas('success');

        $routineCost->refresh();
        $this->assertSame('submitted', $routineCost->status);

        $levels = $routineCost->approvals()->orderBy('level')->get();
        $this->assertSame([1, 2], $levels->pluck('level')->all());
        $this->assertSame(['erkap-ppk', 'erkap-controller'], $levels->pluck('role')->all());
        $this->assertSame($this->ppk->id, $levels[0]->approver_id);
        $this->assertSame($this->controller->id, $levels[1]->approver_id);

        // Level 1 belum cukup: dokumen belum boleh final sebelum controller.
        $this->actingAs($this->ppk)
            ->post(route('erkap.approvals.approve', ['routine_cost', $routineCost->id]), [
                'notes' => 'Anggaran tersedia.',
            ])
            ->assertRedirect();

        $routineCost->refresh();
        $this->assertSame('submitted', $routineCost->status);
        $this->assertSame('pending', $routineCost->approvals()->where('level', 2)->firstOrFail()->status);

        $this->actingAs($this->controller)
            ->post(route('erkap.approvals.approve', ['routine_cost', $routineCost->id]), [
                'notes' => 'Sesuai anggaran.',
            ])
            ->assertRedirect();

        $this->assertSame('approved', $routineCost->refresh()->status);
    }

    public function test_a_role_without_approval_rights_is_rejected_at_every_level(): void
    {
        $this->storeRoutineCost();
        $routineCost = RoutineCost::firstOrFail();
        ApprovalService::submit($routineCost);

        // Accounting punya izin baca Biaya Rutin dan antrean approval, tetapi
        // bukan approver: ia lolos middleware lalu ditolak `requireTurn()`.
        $this->assertTrue($this->accounting->can('erkap.approvals.view'));
        $this->assertFalse($this->accounting->can('erkap.routine-costs.approve'));

        $this->actingAs($this->accounting)
            ->post(route('erkap.approvals.approve', ['routine_cost', $routineCost->id]))
            ->assertRedirect(route('erkap.approvals.show', ['routine_cost', $routineCost->id]))
            ->assertSessionHas('error');

        // Cost owner tidak boleh masuk ke halaman approval sama sekali, jadi
        // penolakannya terjadi di middleware, sebelum controller.
        $this->assertFalse($this->costOwner->can('erkap.approvals.view'));

        $this->actingAs($this->costOwner)
            ->post(route('erkap.approvals.approve', ['routine_cost', $routineCost->id]))
            ->assertForbidden();

        $this->assertSame('submitted', $routineCost->refresh()->status);
        $this->assertSame(
            0,
            $routineCost->approvals()->whereNotNull('approved_at')->count(),
            'Tidak boleh ada approval yang tercatat dari role yang tidak berhak.'
        );
    }

    public function test_an_approved_document_is_locked_against_further_edits(): void
    {
        $this->storeRoutineCost();
        $routineCost = RoutineCost::firstOrFail();
        ApprovalService::submit($routineCost);
        ApprovalService::approve($routineCost, $this->ppk);
        ApprovalService::approve($routineCost, $this->controller);

        $this->actingAs($this->costOwner)
            ->put(route('erkap.routine-costs.update', $routineCost->id), $this->payload([
                'need' => 'Berubah setelah approval',
            ]))
            ->assertRedirect(route('erkap.routine-costs.edit', $routineCost->id))
            ->assertSessionHas('error');

        $this->assertNotSame('Berubah setelah approval', $routineCost->refresh()->need);
    }

    public function test_an_approved_document_cannot_be_deleted_either(): void
    {
        $this->storeRoutineCost();
        $routineCost = RoutineCost::firstOrFail();
        ApprovalService::submit($routineCost);
        ApprovalService::approve($routineCost, $this->ppk);
        ApprovalService::approve($routineCost, $this->controller);

        $this->actingAs($this->costOwner)
            ->delete(route('erkap.routine-costs.destroy', $routineCost->id))
            ->assertRedirect(route('erkap.routine-costs.index'))
            ->assertSessionHas('error');

        $this->assertNotNull($routineCost->fresh());
    }

    /* ---------------------------------------------------------------------
     | Tahap 3 — output
     | ------------------------------------------------------------------ */

    public function test_the_approved_amount_reaches_the_opex_report_with_its_composed_code(): void
    {
        $this->storeRoutineCost();
        $routineCost = RoutineCost::firstOrFail();
        ApprovalService::submit($routineCost);
        ApprovalService::approve($routineCost, $this->ppk);
        ApprovalService::approve($routineCost, $this->controller);

        $rkap = $this->rkap;

        $this->assertGreaterThan(
            0,
            (function () use ($rkap) {
                $this->completeZbbReview($rkap);

                return app(BudgetOpexConsolidationService::class)->consolidate($rkap);
            })(),
            'Konsolidasi OPEX harus menghasilkan baris dari Biaya Rutin yang sudah approved.'
        );

        $report = app(BudgetOpexConsolidationService::class)->getReport($rkap, $this->chain['division']->id);

        $row = $report
            ->get($this->chain['division']->id)['items']
            ->firstWhere('chart_of_account.id', $this->account->id);

        $this->assertNotNull($row, 'Baris COA hasil komposisi harus muncul di laporan OPEX.');
        $this->assertSame($this->account->code, $row['chart_of_account']->code);
        $this->assertSame(15, strlen($row['chart_of_account']->code));

        // Label laporan harus tetap bisa dibaca: kode 15 karakter disegmentasi
        // dan diikuti nama, bukan kode polos tanpa pemisah.
        $this->assertSame(
            CoaCode::format($this->account->code).' - '.$this->account->name,
            $row['chart_of_account']->label
        );

        // Segmen a..d COA harus sama persis dengan kode Pusat Biaya — inilah
        // bukti K-1 di sisi laporan.
        $this->assertSame(
            substr($this->account->code, 0, 11),
            $this->costCenter->code
        );
        $this->assertSame(
            (float) $routineCost->total,
            (float) $row['budget_amount'],
            'Nilai anggaran harus berasal dari Biaya Rutin yang sama.'
        );
    }

    public function test_accounting_can_read_the_composed_code_but_cannot_change_it(): void
    {
        $this->storeRoutineCost();
        $routineCost = RoutineCost::firstOrFail();

        // Kolom Pusat Biaya dan COA di halaman daftar menampilkan kode
        // tersegmentasi, bukan kode polos.
        $this->actingAs($this->accounting)
            ->get(route('erkap.routine-costs.index'))
            ->assertOk()
            ->assertSee(CoaCode::format($this->costCenter->code))
            ->assertSee(CoaCode::format($this->account->code));

        $this->actingAs($this->accounting)
            ->delete(route('erkap.routine-costs.destroy', $routineCost->id))
            ->assertForbidden();

        $this->assertNotNull($routineCost->fresh());
    }

    /* ---------------------------------------------------------------------
     | Helper
     | ------------------------------------------------------------------ */

    /**
     * `ZBBReviewService::requireRationale()` menahan konsolidasi selama ada
     * pos anggaran yang naik tanpa justifikasi yang disetujui. Controller
     * adalah role yang memegang `erkap.zbb-reviews.create/edit`, jadi review
     * diselesaikan atas namanya.
     */
    private function completeZbbReview(RKAP $rkap): void
    {
        foreach (ZBBReview::where('erkap_rkap_id', $rkap->id)->get() as $review) {
            if (! $review->blocksConsolidation()) {
                continue;
            }

            ZBBReviewService::review($rkap, $review->id, $this->controller, [
                'zbb_status' => 'approved',
                'increase_rationale' => 'Kenaikan necessitated oleh kenaikan volume operasional.',
                'review_notes' => 'Disetujui pada acceptance test.',
            ]);
        }
    }

    private function storeRoutineCost(): RoutineCost
    {
        $this->actingAs($this->costOwner)
            ->post(route('erkap.routine-costs.store'), $this->payload())
            ->assertSessionHas('success');

        return RoutineCost::firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        $monthly = array_fill_keys(
            ['jan_cost', 'feb_cost', 'mar_cost', 'apr_cost', 'may_cost', 'jun_cost',
                'jul_cost', 'aug_cost', 'sep_cost', 'oct_cost', 'nov_cost', 'dec_cost'],
            100000
        );

        // `validateTotal()` pada mode bukan kumulatif menuntut total sama
        // dengan qty × harga satuan SEKALigus jumlah seluruh bulanan, jadi
        // qty dibuat 12 paket agar keduanya konsisten — bukan diketik terpisah
        // supaya tidak pernah meleset.
        return array_merge([
            'erkap_work_program_id' => $this->workProgram->id,
            'need' => 'Operasional harian',
            'cost_center_id' => $this->costCenter->id,
            'cost_center_owner' => 'Pemilik Biaya',
            'qty' => 12,
            'units' => 'Paket',
            'unit_price' => 100000,
            'erkap_cost_element_id' => $this->costElement->id,
            'is_kumulatif' => false,
        ], $monthly, ['total' => array_sum($monthly)], $overrides);
    }

    private function userInDivision(string $role, $division): User
    {
        $employee = Employee::create([
            'employee_id' => 'EMP-E2E-'.uniqid(),
            'name' => ucfirst(str_replace('erkap-', '', $role)),
            'division_id' => $division->id,
            'regional_id' => Regional::factory()->create()->id,
        ]);

        $user = User::factory()->create(['employee_id' => $employee->employee_id]);
        $user->assignRole($role);

        return $user;
    }
}
