<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddFkCostCenterToRoutineCostsTable extends Migration
{
    public function up()
    {
        DB::table('erkap_routine_costs')
            ->whereNotNull('cost_center_id')
            ->whereNotIn('cost_center_id', DB::table('cost_centers')->pluck('id'))
            ->update(['cost_center_id' => null]);

        Schema::table('erkap_routine_costs', function (Blueprint $table) {
            $table->foreign('cost_center_id')->references('id')->on('cost_centers')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('erkap_routine_costs', function (Blueprint $table) {
            $table->dropForeign(['cost_center_id']);
        });
    }
}