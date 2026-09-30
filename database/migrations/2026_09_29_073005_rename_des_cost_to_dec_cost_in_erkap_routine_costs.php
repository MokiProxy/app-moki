<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class RenameDesCostToDecCostInErkapRoutineCosts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE erkap_routine_costs DROP CONSTRAINT IF EXISTS erkap_routine_costs_numeric_nonneg');
        DB::statement('ALTER TABLE erkap_routine_costs RENAME COLUMN des_cost TO dec_cost');
        DB::statement('ALTER TABLE erkap_routine_costs ADD CONSTRAINT erkap_routine_costs_numeric_nonneg CHECK (qty >= 0 AND unit_price >= 0 AND total >= 0 AND jan_cost >= 0 AND feb_cost >= 0 AND mar_cost >= 0 AND apr_cost >= 0 AND may_cost >= 0 AND jun_cost >= 0 AND jul_cost >= 0 AND aug_cost >= 0 AND sep_cost >= 0 AND oct_cost >= 0 AND nov_cost >= 0 AND dec_cost >= 0)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE erkap_routine_costs RENAME COLUMN dec_cost TO des_cost');
    }
}
