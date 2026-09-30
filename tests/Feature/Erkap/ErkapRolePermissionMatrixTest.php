<?php

namespace Tests\Feature\Erkap;

use App\Services\ApprovalService;
use App\Services\Erkap\InvestmentGateReviewService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * F10 — audit keamanan matriks permission role ERKAP.
 *
 * Matriks ini diuji lewat seeder sungguhan, bukan lewat daftar manual di
 * dalam test. Kalau seeder yang berubah dan test-nya tidak ikut bergerak,
 * matriks izin bisa bergeser tanpa test gagal — termasuk ketika role kehilangan
 * izin baca yang dibutuhkan form transaksinya.
 */
class ErkapRolePermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    /* ---------------------------------------------------------------------
     | erkap-cost-owner
     | ------------------------------------------------------------------ */

    public function test_cost_owner_can_read_the_structure_behind_its_own_transaction_forms(): void
    {
        $owner = $this->role('erkap-cost-owner');

        // Form Biaya Rutin menunjuk Pusat Biaya, Elemen Biaya, dan COA. Tanpa
        // izin baca ketiganya, dropdown form tetap terisi (api F4 memakai
        // `erkap.menu`) tetapi halaman master menolak dibuka — purchasers
        // tidak bisa memeriksa kode yang terisi sebelum submit.
        foreach ([
            'erkap.structure.view',
            'erkap.cost-centers.view',
            'erkap.cost-elements.view',
            'erkap.chart-of-accounts.view',
        ] as $permission) {
            $this->assertTrue(
                $owner->hasPermissionTo($permission),
                "erkap-cost-owner harus punya {$permission}."
            );
        }
    }

    public function test_cost_owner_cannot_write_the_structure_it_only_reads(): void
    {
        $owner = $this->role('erkap-cost-owner');

        $this->assertFalse($owner->hasPermissionTo('erkap.structure.edit'));

        foreach (['business-units', 'locations', 'management-areas', 'activities', 'cost-centers', 'cost-elements', 'chart-of-accounts'] as $resource) {
            foreach (['create', 'edit', 'delete'] as $action) {
                $this->assertFalse(
                    $owner->hasPermissionTo("erkap.{$resource}.{$action}"),
                    "erkap-cost-owner tidak boleh punya erkap.{$resource}.{$action}."
                );
            }
        }
    }

    public function test_cost_owner_cannot_approve_its_own_submission(): void
    {
        $owner = $this->role('erkap-cost-owner');

        // Cost owner submitting RoutineCost tidak boleh sekaligus menjadi
        // approver-nya; persetujuan lintas divisi memang tugas PPK/Controller.
        $this->assertTrue($owner->hasPermissionTo('erkap.routine-costs.submit'));
        $this->assertFalse($owner->hasPermissionTo('erkap.routine-costs.approve'));
        $this->assertFalse($owner->hasPermissionTo('erkap.routine-costs.reject'));
    }

    /* ---------------------------------------------------------------------
     | Pemisahan tugasan antar role
     | ------------------------------------------------------------------ */

    public function test_every_role_in_the_approval_matrix_can_approve_and_reject(): void
    {
        // Diturunkan dari `ApprovalService::getApprovalMatrix()` supaya test
        // tidak memakai matrix hard-code yang bisa menyimpang dari implementasi.
        $resources = [
            'work_program' => 'work-programs',
            'routine_cost' => 'routine-costs',
            'investment_plan' => 'investment-plans',
            'rkap' => 'rkap',
            'risk_register' => 'risk-identifications',
        ];

        foreach (ApprovalService::getApprovalMatrix() as $type => $levels) {
            foreach ($levels as $roleName) {
                $role = $this->role($roleName);
                $resource = $resources[$type];

                $this->assertTrue(
                    $role->hasPermissionTo('erkap.approvals.view'),
                    "{$roleName} ada di rantai approval {$type} tanpa izin melihat antrean."
                );
                $this->assertTrue(
                    $role->hasPermissionTo("erkap.{$resource}.approve"),
                    "{$roleName} ada di rantai approval {$type} tanpa izin erkap.{$resource}.approve."
                );
                $this->assertTrue(
                    $role->hasPermissionTo("erkap.{$resource}.reject"),
                    "{$roleName} ada di rantai approval {$type} tanpa izin erkap.{$resource}.reject."
                );
            }
        }
    }

    public function test_every_stage_gate_reviewer_can_review_the_gate(): void
    {
        // `approve()` menolak sebelum gate level selesai, jadi tiap reviewer
        // pada `InvestmentGateReviewService::STAGES` harus punya izin review —
        // tanpa itu, Rencana Investasi tidak akan pernah lolos approval.
        foreach (InvestmentGateReviewService::STAGES as $stage => $meta) {
            $role = $this->role($meta['role']);

            $this->assertTrue(
                $role->hasPermissionTo('erkap.investment-gates.review'),
                "{$meta['role']} mereview stage {$stage} tanpa izin erkap.investment-gates.review."
            );
            $this->assertTrue(
                $role->hasPermissionTo('erkap.investment-gates.view'),
                "{$meta['role']} mereview stage {$stage} tanpa izin erkap.investment-gates.view."
            );
        }
    }

    public function test_auditor_is_read_only_everywhere(): void
    {
        $auditor = $this->role('erkap-auditor');

        $this->assertTrue($auditor->hasPermissionTo('erkap.menu'));
        $this->assertTrue($auditor->hasPermissionTo('erkap.audit-logs.view'));
        $this->assertTrue($auditor->hasPermissionTo('erkap.cost-centers.view'));

        $mutating = $auditor->permissions
            ->pluck('name')
            ->filter(fn (string $name) => $name !== 'erkap.menu'
                && ! str_ends_with($name, '.view')
                && ! in_array($name, ['erkap.audit-logs.view'], true));

        $this->assertCount(0, $mutating, 'Auditor tidak boleh punya izin mutasi: '.$mutating->implode(', '));
    }

    public function test_only_erkap_admin_may_edit_the_segment_structure(): void
    {
        $this->assertTrue($this->role('erkap-admin')->hasPermissionTo('erkap.structure.edit'));
        $this->assertTrue($this->role('super-admin')->hasPermissionTo('erkap.structure.edit'));

        $readOnly = [
            'erkap-ppk', 'erkap-controller', 'erkap-direksi-keuangan', 'erkap-direksi',
            'erkap-komisaris', 'erkap-accounting', 'erkap-risk-manager',
            'erkap-manajemen-aset', 'erkap-cost-owner', 'erkap-auditor',
        ];

        foreach ($readOnly as $roleName) {
            $this->assertFalse(
                $this->role($roleName)->hasPermissionTo('erkap.structure.edit'),
                "{$roleName} tidak boleh mengubah struktur master a..d."
            );
        }
    }

    private function role(string $name): Role
    {
        return Role::where('name', $name)->firstOrFail();
    }
}
