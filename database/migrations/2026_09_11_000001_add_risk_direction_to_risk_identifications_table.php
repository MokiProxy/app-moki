<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRiskDirectionToRiskIdentificationsTable extends Migration
{
    public function up()
    {
        Schema::table('erkap_risk_identifications', function (Blueprint $table) {
            $table->enum('risk_direction', ['positive', 'negative'])
                ->default('negative')
                ->after('risk');
        });
    }

    public function down()
    {
        Schema::table('erkap_risk_identifications', function (Blueprint $table) {
            $table->dropColumn('risk_direction');
        });
    }
}