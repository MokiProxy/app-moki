<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UnifyRiskStrategyTerminology extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE erkap_risk_treatments
               SET treatment_type = CASE treatment_type
                    WHEN 'avoid'    THEN 'avoidance'
                    WHEN 'reduce'   THEN 'reduction'
                    WHEN 'mitigate' THEN 'reduction'
                    WHEN 'transfer' THEN 'sharing'
                    WHEN 'accept'   THEN 'acceptance'
                    ELSE treatment_type END
             WHERE treatment_type IN ('avoid','reduce','mitigate','transfer','accept')
        ");

        DB::statement('ALTER TABLE erkap_risk_treatments ALTER COLUMN treatment_type TYPE VARCHAR(12)');

        DB::statement("ALTER TABLE erkap_risk_treatments ADD CONSTRAINT erkap_risk_treatments_treatment_type_check CHECK (treatment_type IN ('avoidance','reduction','sharing','acceptance'))");

        DB::statement("
            UPDATE erkap_department_risk_strategies
               SET strategy = CASE strategy
                    WHEN 'avoid'    THEN 'avoidance'
                    WHEN 'reduce'   THEN 'reduction'
                    WHEN 'transfer' THEN 'sharing'
                    WHEN 'accept'   THEN 'acceptance'
                    ELSE strategy END
             WHERE strategy IN ('avoid','reduce','transfer','accept')
        ");

        DB::statement('ALTER TABLE erkap_department_risk_strategies DROP CONSTRAINT IF EXISTS erkap_department_risk_strategies_strategy_check');

        DB::statement('ALTER TABLE erkap_department_risk_strategies ALTER COLUMN strategy TYPE VARCHAR(12)');

        DB::statement("ALTER TABLE erkap_department_risk_strategies ADD CONSTRAINT erkap_department_risk_strategies_strategy_check CHECK (strategy IN ('avoidance','reduction','sharing','acceptance'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE erkap_risk_treatments DROP CONSTRAINT IF EXISTS erkap_risk_treatments_treatment_type_check');

        DB::statement('ALTER TABLE erkap_risk_treatments ALTER COLUMN treatment_type TYPE TEXT');

        DB::statement('ALTER TABLE erkap_department_risk_strategies DROP CONSTRAINT IF EXISTS erkap_department_risk_strategies_strategy_check');

        DB::statement('ALTER TABLE erkap_department_risk_strategies ALTER COLUMN strategy TYPE TEXT');
    }
}