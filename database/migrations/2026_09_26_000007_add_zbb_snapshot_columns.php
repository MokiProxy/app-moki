<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddZbbSnapshotColumns extends Migration
{
    public function up()
    {
        Schema::table('erkap_routine_costs', function (Blueprint $table) {
            $table->decimal('prior_year_amount', 20, 2)->nullable()->after('total');
        });

        Schema::table('erkap_investment_plans', function (Blueprint $table) {
            $table->decimal('prior_year_amount', 20, 2)->nullable()->after('total');
        });

        Schema::table('erkap_revenue_plans', function (Blueprint $table) {
            $table->decimal('prior_year_amount', 20, 2)->nullable()->after('total');
        });
    }

    public function down()
    {
        Schema::table('erkap_revenue_plans', function (Blueprint $table) {
            $table->dropColumn('prior_year_amount');
        });

        Schema::table('erkap_investment_plans', function (Blueprint $table) {
            $table->dropColumn('prior_year_amount');
        });

        Schema::table('erkap_routine_costs', function (Blueprint $table) {
            $table->dropColumn('prior_year_amount');
        });
    }
}