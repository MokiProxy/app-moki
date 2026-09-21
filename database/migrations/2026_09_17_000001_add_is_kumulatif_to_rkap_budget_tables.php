<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsKumulatifToRkapBudgetTables extends Migration
{
    public function up()
    {
        Schema::table('erkap_routine_costs', function (Blueprint $table) {
            $table->boolean('is_kumulatif')->default(false)->after('total');
        });

        Schema::table('erkap_investment_plans', function (Blueprint $table) {
            $table->boolean('is_kumulatif')->default(false)->after('total');
        });
    }

    public function down()
    {
        Schema::table('erkap_routine_costs', function (Blueprint $table) {
            $table->dropColumn('is_kumulatif');
        });

        Schema::table('erkap_investment_plans', function (Blueprint $table) {
            $table->dropColumn('is_kumulatif');
        });
    }
}