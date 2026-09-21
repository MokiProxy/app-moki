<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceScorecardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erkap_performance_scorecards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('erkap_rkap_id')->constrained('erkap_rkap')->cascadeOnDelete();
            $table->foreignId('erkap_department_target_id')->constrained('erkap_department_targets')->cascadeOnDelete();
            $table->tinyInteger('quarter');
            $table->smallInteger('year');
            $table->string('kpi_name', 255);
            $table->decimal('kpi_target', 15, 2)->default(0);
            $table->decimal('kpi_actual', 15, 2)->default(0);
            $table->decimal('kpi_score', 5, 2)->default(0);
            $table->decimal('weight', 5, 2)->default(0);
            $table->decimal('weighted_score', 5, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['erkap_department_target_id', 'quarter', 'year', 'kpi_name'], 'unique_performance_scorecard');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('erkap_performance_scorecards');
    }
}