<?php

namespace Database\Factories\Erkap;

use App\Models\Erkap\ReportItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportItemFactory extends Factory
{
    protected $model = ReportItem::class;

    public function definition(): array
    {
        return [
            'report_type' => 'rkap',
            'title' => $this->faker->sentence(3),
            'frequency' => 'manual',
            'year' => (int) date('Y'),
            'month' => null,
            'format' => 'pdf',
            'file_path' => 'reports/example.pdf',
            'status' => 'success',
            'error' => null,
        ];
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'error' => 'Terjadi kesalahan saat membuat laporan',
        ]);
    }

    public function excel(): static
    {
        return $this->state(fn () => ['format' => 'excel']);
    }
}