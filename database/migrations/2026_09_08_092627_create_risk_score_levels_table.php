<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRiskScoreLevelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erkap_risk_score_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('erkap_risk_probability_id')->constrained('erkap_risk_probabilities')->cascadeOnDelete();
            $table->foreignId('erkap_risk_impact_id')->constrained('erkap_risk_impacts')->cascadeOnDelete();
            $table->integer('score');
            $table->enum('level', ['Low', 'Low To Moderate', 'Moderate', 'Moderate To High', 'High']);
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
        Schema::dropIfExists('erkap_risk_score_levels');
    }
}
