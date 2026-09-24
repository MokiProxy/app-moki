<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\WorkProgram;
use App\Models\Erkap\InvestattionCategory;
use App\Models\Erkap\InvestationType;
use App\Models\Erkap\InvestationCriteria;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvestmentPlanFactory extends Factory
{
    protected $model = InvestmentPlan::class;

    public function definition(): array
    {
        return [
            'erkap_work_program_id' => WorkProgram::factory(),
            'erkap_investattion_category_id' => InvestattionCategory::factory(),
            'erkap_investation_type_id' => InvestationType::factory(),
            'erkap_investation_criteria_id' => InvestationCriteria::factory(),
            'name' => $this->faker->sentence(),
            'description' => $this->faker->sentence(),
            'unit' => $this->faker->word(),
            'qty' => $this->faker->numberBetween(1, 50),
            'unit_price' => $this->faker->numberBetween(10000, 1000000),
            'jan_plan' => $this->faker->numberBetween(10000, 500000),
            'feb_plan' => $this->faker->numberBetween(10000, 500000),
            'mar_plan' => $this->faker->numberBetween(10000, 500000),
            'apr_plan' => $this->faker->numberBetween(10000, 500000),
            'may_plan' => $this->faker->numberBetween(10000, 500000),
            'jun_plan' => $this->faker->numberBetween(10000, 500000),
            'jul_plan' => $this->faker->numberBetween(10000, 500000),
            'aug_plan' => $this->faker->numberBetween(10000, 500000),
            'sep_plan' => $this->faker->numberBetween(10000, 500000),
            'oct_plan' => $this->faker->numberBetween(10000, 500000),
            'nov_plan' => $this->faker->numberBetween(10000, 500000),
            'dec_plan' => $this->faker->numberBetween(10000, 500000),
            'total' => fn (array $attributes) => $attributes['qty'] * $attributes['unit_price'],
            'is_kumulatif' => false,
            'status' => 'draft',
        ];
    }

    public function withProposal(): static
    {
        return $this->state(fn () => [
            'proposal_file_path' => 'investment-plans/proposals/fake-proposal.pdf',
            'proposal_original_name' => 'fake-proposal.pdf',
        ]);
    }

    public function withCba(): static
    {
        return $this->state(fn () => [
            'cba_json' => [
                'npv' => 120000000.00,
                'irr' => 18.50,
                'payback' => 3.25,
                'justification' => 'Investasi layak berdasarkan analisis kelayakan.',
            ],
            'cba_attachment_path' => 'investment-plans/cba/fake-cba.xlsx',
        ]);
    }
}