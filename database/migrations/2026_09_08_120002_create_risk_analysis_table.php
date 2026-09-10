<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRiskAnalysisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erkap_risk_analysis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('erkap_risk_identification_id')->constrained('erkap_risk_identifications')->cascadeOnDelete();
            $table->foreignId('erkap_risk_probability_id')->constrained('erkap_risk_probabilities')->cascadeOnDelete();
            $table->foreignId('erkap_risk_impact_id')->constrained('erkap_risk_impacts')->cascadeOnDelete();
            $table->foreignId('erkap_risk_score_value_id')->constrained('erkap_risk_score_levels')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('erkap_risk_analysis');
    }
}
