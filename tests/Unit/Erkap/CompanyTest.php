<?php

namespace Tests\Unit\Erkap;

use App\Models\Company;
use App\Models\Division;
use App\Models\Erkap\CompanyTarget;
use App\Models\Erkap\RKAP;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_has_many_divisions(): void
    {
        $company = Company::create([
            'name' => 'PT Induk',
            'code' => 'IDK',
            'is_parent' => true,
        ]);

        Division::factory()->count(2)->create(['company_id' => $company->id]);

        $this->assertCount(2, $company->divisions);
    }

    public function test_company_hierarchy_parent_and_child(): void
    {
        $parent = Company::create([
            'name' => 'PT Induk',
            'code' => 'IDK',
            'is_parent' => true,
        ]);

        $child = Company::create([
            'name' => 'PT Anak',
            'code' => 'ANC',
            'parent_company_id' => $parent->id,
        ]);

        $parent = $parent->fresh();
        $child = $child->fresh();

        $this->assertTrue($child->parentCompany->is($parent));
        $this->assertTrue($parent->childCompanies->contains($child));
        $this->assertTrue($parent->is_parent);
        $this->assertFalse($child->is_parent);
    }

    public function test_company_has_many_company_targets(): void
    {
        $company = Company::create([
            'name' => 'PT Induk',
            'code' => 'IDK',
        ]);

        CompanyTarget::factory()->count(2)->create(['company_id' => $company->id]);

        $this->assertCount(2, $company->companyTargets);
    }

    public function test_rkap_belongs_to_company(): void
    {
        $company = Company::create([
            'name' => 'PT Induk',
            'code' => 'IDK',
        ]);

        $rkap = RKAP::factory()->create([
            'year' => 2025,
            'company_id' => $company->id,
        ]);

        $this->assertTrue($rkap->company->is($company));
    }

    public function test_multi_company_isolation(): void
    {
        $companyA = Company::create(['name' => 'PT A', 'code' => 'PTA']);
        $companyB = Company::create(['name' => 'PT B', 'code' => 'PTB']);

        $rkapA = RKAP::factory()->create(['year' => 2025, 'company_id' => $companyA->id]);
        $rkapB = RKAP::factory()->create(['year' => 2025, 'company_id' => $companyB->id]);

        $targetA = CompanyTarget::factory()->create([
            'company_id' => $companyA->id,
            'erkap_rkap_id' => $rkapA->id,
        ]);
        CompanyTarget::factory()->create([
            'company_id' => $companyB->id,
            'erkap_rkap_id' => $rkapB->id,
        ]);

        $this->assertDatabaseHas('erkap_company_targets', [
            'id' => $targetA->id,
            'company_id' => $companyA->id,
        ]);

        $this->assertSame(
            $companyA->id,
            $companyA->companyTargets()->first()->company_id
        );
        $this->assertSame(
            $companyA->id,
            $rkapA->companyTargets()->first()->company_id
        );
        $this->assertCount(1, $companyA->companyTargets);
    }
}