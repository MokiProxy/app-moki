<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRiskAssessmentsMonthlyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erkap_risk_assessments_monthly', function (Blueprint $table) {
            $table->id();
            $table->foreignId('erkap_risk_identification_id')->constrained('erkap_risk_identifications')->cascadeOnDelete();
            $table->tinyInteger('month');
            $table->smallInteger('year');
            $table->tinyInteger('inherent_probability');
            $table->tinyInteger('inherent_impact');
            $table->integer('inherent_score');
            $table->tinyInteger('current_probability')->nullable();
            $table->tinyInteger('current_impact')->nullable();
            $table->integer('current_score')->nullable();
            $table->tinyInteger('residual_probability')->nullable();
            $table->tinyInteger('residual_impact')->nullable();
            $table->integer('residual_score')->nullable();
            $table->text('mitigation_plan')->nullable();
            $table->enum('mitigation_status', ['on_progress', 'done', 'overdue'])->default('on_progress');
            $table->string('risk_owner', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['erkap_risk_identification_id', 'month', 'year'], 'unique_risk_assessment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('erkap_risk_assessments_monthly');
    }
}