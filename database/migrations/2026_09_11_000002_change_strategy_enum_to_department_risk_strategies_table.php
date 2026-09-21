<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ChangeStrategyEnumToDepartmentRiskStrategiesTable extends Migration
{
    public function up()
    {
        DB::table('erkap_department_risk_strategies')
            ->whereNotNull('strategy')
            ->whereNotIn('strategy', ['avoid', 'reduce', 'transfer', 'accept'])
            ->update(['strategy' => 'reduce']);

        DB::statement('ALTER TABLE erkap_department_risk_strategies ALTER COLUMN strategy TYPE VARCHAR(10)');

        DB::statement("ALTER TABLE erkap_department_risk_strategies ADD CONSTRAINT erkap_department_risk_strategies_strategy_check CHECK (strategy IN ('avoid', 'reduce', 'transfer', 'accept'))");
    }

    public function down()
    {
        DB::statement('ALTER TABLE erkap_department_risk_strategies DROP CONSTRAINT IF EXISTS erkap_department_risk_strategies_strategy_check');

        DB::statement('ALTER TABLE erkap_department_risk_strategies ALTER COLUMN strategy TYPE TEXT');
    }
}