<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'year_plan',
        'jan_plan',
        'feb_plan',
        'mar_plan',
        'apr_plan',
        'may_plan',
        'jun_plan',
        'jul_plan',
        'aug_plan',
        'sep_plan',
        'oct_plan',
        'nov_plan',
        'dec_plan',
    ];

    private const CONSTRAINT = 'erkap_work_programs_plan_percentages';

    public function up(): void
    {
        if (! Schema::hasTable('erkap_work_programs') || DB::getDriverName() !== 'pgsql') {
            return;
        }

        $this->normalizeLegacyValues();

        foreach (self::COLUMNS as $column) {
            DB::statement(
                "ALTER TABLE erkap_work_programs ALTER COLUMN {$column} TYPE numeric(5,2) USING ROUND({$column}::numeric, 2)"
            );
        }

        if (! $this->constraintExists()) {
            $conditions = implode(' AND ', array_map(
                fn (string $column) => "({$column} IS NULL OR ({$column} >= 0 AND {$column} <= 100))",
                self::COLUMNS
            ));

            DB::statement("ALTER TABLE erkap_work_programs ADD CONSTRAINT " . self::CONSTRAINT . " CHECK ({$conditions})");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('erkap_work_programs') || DB::getDriverName() !== 'pgsql') {
            return;
        }

        if ($this->constraintExists()) {
            DB::statement('ALTER TABLE erkap_work_programs DROP CONSTRAINT ' . self::CONSTRAINT);
        }

        foreach (self::COLUMNS as $column) {
            DB::statement("ALTER TABLE erkap_work_programs ALTER COLUMN {$column} TYPE double precision USING {$column}::double precision");
        }
    }

    private function normalizeLegacyValues(): void
    {
        $query = DB::table('erkap_work_programs');

        foreach (self::COLUMNS as $column) {
            $query->orWhere(function ($query) use ($column) {
                $query->whereNotNull($column)->where($column, '>', 100);
            });
        }

        if (! $query->exists()) {
            return;
        }

        DB::table('erkap_work_programs')
            ->select(array_merge(['id'], self::COLUMNS))
            ->orderBy('id')
            ->chunk(100, function ($rows) {
                foreach ($rows as $row) {
                    $values = collect(self::COLUMNS)
                        ->map(fn (string $column) => (float) ($row->{$column} ?? 0))
                        ->all();
                    $monthlyTotal = array_sum(array_slice($values, 1));
                    $denominator = max($values[0], $monthlyTotal);

                    if ($denominator <= 0) {
                        continue;
                    }

                    $updates = ['year_plan' => 100];
                    $allocated = 0;

                    foreach (array_slice(self::COLUMNS, 1, 11) as $column) {
                        $index = array_search($column, self::COLUMNS, true);
                        $percentage = round($values[$index] / $denominator * 100, 2);
                        $updates[$column] = $percentage;
                        $allocated += $percentage;
                    }

                    $updates['dec_plan'] = round(100 - $allocated, 2);
                    DB::table('erkap_work_programs')->where('id', $row->id)->update($updates);
                }
            });
    }

    private function constraintExists(): bool
    {
        return DB::selectOne(
            'SELECT 1 FROM pg_constraint WHERE conname = ? LIMIT 1',
            [self::CONSTRAINT]
        ) !== null;
    }
};
