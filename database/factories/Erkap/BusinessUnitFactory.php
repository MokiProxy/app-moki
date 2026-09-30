<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\BusinessUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BusinessUnitFactory extends Factory
{
    protected $model = BusinessUnit::class;

    public function definition(): array
    {
        return [
            // Segmen (a) hanya 1 karakter, jadi ruang kode sangat sempit
            // (36 kombinasi). `unique()` faker hanya berlaku per instance dan
            // tetap bentrok dengan data lain, sehingga dipakai cek ke DB.
            'code' => $this->unusedCode(),
            'name' => $this->faker->unique()->company(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    private function unusedCode(): string
    {
        $taken = BusinessUnit::query()->pluck('code')->all();
        $alphabet = array_merge(range('A', 'Z'), range('0', '9'));

        do {
            $code = Str::upper($this->faker->randomElement($alphabet));
        } while (in_array($code, $taken, true));

        return $code;
    }
}
