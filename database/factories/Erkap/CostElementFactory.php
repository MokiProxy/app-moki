<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\CostElement;
use App\Models\Erkap\CostElementCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class CostElementFactory extends Factory
{
    protected $model = CostElement::class;

    public function definition(): array
    {
        return [
            // Segmen (e) pada kode COA tepat 4 digit, jadi kode elemen harus
            // 4 digit — bukan 6 karakter seperti skema lama.
            'code' => $this->uniqueFourDigitCode(),
            'name' => $this->faker->word(),
            'erkap_cost_element_category_id' => CostElementCategory::factory(),
        ];
    }

    private function uniqueFourDigitCode(): string
    {
        $taken = CostElement::query()->pluck('code')->all();

        do {
            $code = $this->faker->unique()->numerify('####');
        } while (in_array($code, $taken, true));

        return $code;
    }
}
