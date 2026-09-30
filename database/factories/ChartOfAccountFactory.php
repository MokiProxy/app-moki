<?php

namespace Database\Factories;

use App\Models\ChartOfAccount;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\CostElement;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChartOfAccountFactory extends Factory
{
    protected $model = ChartOfAccount::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->regexify('[A-Z][0-9]{14}'),
            'name' => $this->faker->sentence(3),
            'type' => 'expense',
            'description' => $this->faker->sentence(),
        ];
    }

    /**
     * COA yang kodenya disusun otomatis oleh model dari Pusat Biaya (a..d)
     * dan Elemen Biaya (e).
     */
    public function composed(?CostCenter $costCenter = null, ?CostElement $costElement = null): static
    {
        $costCenter = $costCenter ?: CostCenter::factory()->create();
        $costElement = $costElement ?: CostElement::factory()->create();

        return $this->state(fn () => [
            'cost_center_id' => $costCenter->id,
            'cost_element_id' => $costElement->id,
        ]);
    }

    public function revenue(): static
    {
        return $this->state(fn () => ['type' => 'revenue']);
    }

    public function expense(): static
    {
        return $this->state(fn () => ['type' => 'expense']);
    }
}
