<?php

namespace Tests\Unit\Erkap;

use App\Models\Erkap\RKAP;
use App\Models\Erkap\RiskIdentification;
use App\Models\Erkap\WorkProgram;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalMatrixTest extends TestCase
{
    use RefreshDatabase;
    public function test_rkap_approval_order_komisaris_then_direksi(): void
    {
        $matrix = ApprovalService::getApprovalMatrix();
        
        $this->assertArrayHasKey('rkap', $matrix);
        $rkapMatrix = $matrix['rkap'];
        
        $this->assertEquals('erkap-komisaris', $rkapMatrix[1]);
        $this->assertEquals('erkap-direksi', $rkapMatrix[2]);
        $this->assertCount(2, $rkapMatrix);
    }

    public function test_risk_register_approval_workflow(): void
    {
        $matrix = ApprovalService::getApprovalMatrix();
        
        $this->assertArrayHasKey('risk_register', $matrix);
        $riskRegisterMatrix = $matrix['risk_register'];
        
        $this->assertEquals('erkap-risk-manager', $riskRegisterMatrix[1]);
        $this->assertCount(1, $riskRegisterMatrix);
    }

    public function test_document_type_for_risk_identification(): void
    {
        $risk = RiskIdentification::factory()->make();
        
        $type = ApprovalService::typeFor($risk);
        
        $this->assertEquals('risk_register', $type);
    }

    public function test_document_type_for_work_program(): void
    {
        $program = WorkProgram::factory()->make();
        
        $type = ApprovalService::typeFor($program);
        
        $this->assertEquals('work_program', $type);
    }

    public function test_document_type_for_rkap(): void
    {
        $rkap = RKAP::factory()->make();
        
        $type = ApprovalService::typeFor($rkap);
        
        $this->assertEquals('rkap', $type);
    }
}