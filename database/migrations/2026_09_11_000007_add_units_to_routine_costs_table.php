<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnitsToRoutineCostsTable extends Migration
{
    public function up()
    {
        Schema::table('erkap_routine_costs', function (Blueprint $table) {
            $table->string('units', 50)->default('Unit')->after('qty');
        });
    }

    public function down()
    {
        Schema::table('erkap_routine_costs', function (Blueprint $table) {
            $table->dropColumn('units');
        });
    }
}