<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check constraints for risk_probabilities.point (1-5)
        $this->addCheck('erkap_risk_probabilities', 'erkap_risk_probabilities_point_range', 'point BETWEEN 1 AND 5');

        // Check constraints for risk_impacts.point (1-5)
        $this->addCheck('erkap_risk_impacts', 'erkap_risk_impacts_point_range', 'point BETWEEN 1 AND 5');

        // Check constraints for routine_costs
        $this->addCheck('erkap_routine_costs', 'erkap_routine_costs_qty_positive', 'qty > 0');

        // Check constraints for investment_plans
        $this->addCheck('erkap_investment_plans', 'erkap_investment_plans_qty_positive', 'qty > 0');
        $this->addCheck('erkap_investment_plans', 'erkap_investment_plans_unit_price_nonneg', 'unit_price >= 0');
        $this->addCheck('erkap_investment_plans', 'erkap_investment_plans_total_calc', 'total = qty * unit_price');
    }

    public function down(): void
    {
        $checks = [
            'erkap_risk_probabilities_point_range',
            'erkap_risk_impacts_point_range',
            'erkap_routine_costs_qty_positive',
            'erkap_investment_plans_qty_positive',
            'erkap_investment_plans_unit_price_nonneg',
            'erkap_investment_plans_total_calc',
        ];

        foreach ($checks as $name) {
            if ($this->constraintExists($name)) {
                DB::statement("ALTER TABLE " . $this->tableFor($name) . " DROP CONSTRAINT {$name}");
            }
        }
    }

    protected function addCheck(string $table, string $name, string $condition): void
    {
        if (!Schema::hasTable($table) || $this->constraintExists($name)) {
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

    protected function tableFor(string $constraint): string
    {
        $row = DB::selectOne(
            "SELECT t.relname FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid WHERE c.conname = ?",
            [$constraint]
        );

        return $row->relname ?? abort(500, "Constraint {$constraint} tidak ditemukan.");
    }
};
