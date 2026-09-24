<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ----- Unique constraint untuk kolom kode -----
        foreach (['erkap_cost_elements', 'erkap_investation_types', 'erkap_investation_criterias', 'erkap_investattion_categories', 'cost_centers'] as $table) {
            $this->addUniqueIfClean($table, 'code');
        }

        // ----- Check constraint non-negatif numerik -----
        $monthWhole = function (string $prefix, array $months): string {
            return implode(' AND ', array_map(
                fn (string $month) => "{$prefix}{$month} >= 0",
                $months
            ));
        };

        $costMonths = ['jan_cost', 'feb_cost', 'mar_cost', 'apr_cost', 'may_cost', 'jun_cost', 'jul_cost', 'aug_cost', 'sep_cost', 'oct_cost', 'nov_cost', 'des_cost'];
        $planMonths = ['jan_plan', 'feb_plan', 'mar_plan', 'apr_plan', 'may_plan', 'jun_plan', 'jul_plan', 'aug_plan', 'sep_plan', 'oct_plan', 'nov_plan', 'dec_plan'];

        $checks = [
            'erkap_routine_costs' => [
                'erkap_routine_costs_numeric_nonneg',
                'qty >= 0 AND unit_price >= 0 AND total >= 0 AND ' . $monthWhole('', $costMonths),
            ],
            'erkap_work_programs' => [
                'erkap_work_programs_numeric_nonneg',
                'year_plan >= 0 AND ' . $monthWhole('', $planMonths),
            ],
            'erkap_investment_plans' => [
                'erkap_investment_plans_numeric_nonneg',
                'total >= 0 AND ' . $monthWhole('', $planMonths),
            ],
            'erkap_revenue_plans' => [
                'erkap_revenue_plans_numeric_nonneg',
                'total >= 0 AND ' . $monthWhole('', $planMonths),
            ],
            'erkap_expense_plans' => [
                'erkap_expense_plans_numeric_nonneg',
                'total >= 0 AND ' . $monthWhole('', $planMonths),
            ],
            'erkap_budget_realizations' => [
                'erkap_budget_realizations_numeric_nonneg',
                'budgeted >= 0 AND realized >= 0',
            ],
            'erkap_performance_scorecards' => [
                'erkap_performance_scorecards_numeric_nonneg',
                'kpi_target >= 0 AND kpi_actual >= 0 AND kpi_score >= 0 AND weight >= 0 AND weighted_score >= 0',
            ],
            'erkap_risk_assessments_monthly' => [
                'erkap_risk_assessments_monthly_scores_nonneg',
                'inherent_score >= 0 AND current_score >= 0 AND residual_score >= 0',
            ],
        ];

        foreach ($checks as $table => [$name, $condition]) {
            $this->addCheck($table, $name, $condition);
        }
    }

    public function down(): void
    {
        foreach (['erkap_cost_elements', 'erkap_investation_types', 'erkap_investation_criterias', 'erkap_investattion_categories'] as $table) {
            if (Schema::hasTable($table) && $this->constraintExists("{$table}_code_unique")) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropUnique($blueprint->getTable() . '_code_unique');
                });
            }
        }

        $checks = [
            'erkap_routine_costs_numeric_nonneg',
            'erkap_work_programs_numeric_nonneg',
            'erkap_investment_plans_numeric_nonneg',
            'erkap_revenue_plans_numeric_nonneg',
            'erkap_expense_plans_numeric_nonneg',
            'erkap_budget_realizations_numeric_nonneg',
            'erkap_performance_scorecards_numeric_nonneg',
            'erkap_risk_assessments_monthly_scores_nonneg',
        ];

        foreach ($checks as $name) {
            if ($this->constraintExists($name)) {
                DB::statement("ALTER TABLE " . $this->tableFor($name) . " DROP CONSTRAINT {$name}");
            }
        }
    }

    protected function addUniqueIfClean(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        if ($this->constraintExists("{$table}_{$column}_unique") || $this->columnHasUniqueConstraint($table, $column)) {
            return;
        }

        $total = (int) DB::table($table)->count();

        if ($total > 0 && $total !== (int) DB::table($table)->distinct()->count($column)) {
            $this->command?->warn("Skip unique({$column}) pad {$table}: terdapat nilai duplikat.");

            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->unique($column, "{$blueprint->getTable()}_{$column}_unique");
        });
    }

    protected function addCheck(string $table, string $name, string $condition): void
    {
        if (! Schema::hasTable($table) || $this->constraintExists($name)) {
            return;
        }

        $violations = (int) DB::table($table)->whereRaw('NOT (' . $condition . ')')->count();

        if ($violations > 0) {
            $this->command?->warn("Skip check {$name}: {$violations} baris melanggar constraint.");

            return;
        }

        DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$condition})");
    }

    protected function constraintExists(string $name): bool
    {
        return DB::selectOne(
            "SELECT 1 FROM pg_constraint WHERE conname = ? LIMIT 1",
            [$name]
        ) !== null;
    }

    protected function columnHasUniqueConstraint(string $table, string $column): bool
    {
        return DB::selectOne(
            "SELECT 1
               FROM pg_constraint c
               JOIN pg_class t ON t.oid = c.conrelid
               JOIN pg_namespace n ON n.oid = t.relnamespace
               JOIN LATERAL unnest(c.conkey) k(attnum) ON true
               JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = k.attnum
              WHERE n.nspname = current_schema()
                AND t.relname = ?
                AND a.attname = ?
                AND c.contype IN ('p', 'u')
              LIMIT 1",
            [$table, $column]
        ) !== null;
    }

    protected function tableFor(string $constraint): string
    {
        $row = DB::selectOne(
            "SELECT t.relname FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid WHERE c.conname = ?",
            [$constraint]
        );

        return $row->relname ?? abort(500, "Constraint {$constraint} tidak ditemukan.");
    }
};