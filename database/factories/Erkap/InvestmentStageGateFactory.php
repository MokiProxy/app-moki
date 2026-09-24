<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\InvestmentStageGate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvestmentStageGateFactory extends Factory
{
    protected $model = InvestmentStageGate::class;

    public function definition(): array
    {
        return [
            'erkap_investment_plan_id' => InvestmentPlan::factory(),
            'stage' => 'proposal',
            'stage_order' => 1,
            'status' => 'pending',
            'reviewer_role' => 'erkap-ppk',
        ];
    }

    public function proposal(): static
    {
        return $this->state(fn () => [
            'stage' => 'proposal',
            'stage_order' => 1,
            'reviewer_role' => 'erkap-ppk',
        ]);
    }

    public function cba(): static
    {
        return $this->state(fn () => [
            'stage' => 'cba',
            'stage_order' => 2,
            'reviewer_role' => 'erkap-ppk',
        ]);
    }

    public function aset(): static
    {
        return $this->state(fn () => [
            'stage' => 'aset',
            'stage_order' => 3,
            'reviewer_role' => 'erkap-manajemen-aset',
        ]);
    }

    public function direksiKeuangan(): static
    {
        return $this->state(fn () => [
            'stage' => 'direksi_keuangan',
            'stage_order' => 4,
            'reviewer_role' => 'erkap-direksi-keuangan',
        ]);
    }

    public function gateReview(): static
    {
        return $this->state(fn () => [
            'stage' => 'gate_review_bmi',
            'stage_order' => 5,
            'reviewer_role' => 'erkap-gate-review',
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'result' => 'layak',
            'reviewed_at' => now(),
            'reviewed_by' => User::factory(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => 'pending',
            'reviewed_at' => null,
            'reviewed_by' => null,
        ]);
    }
}