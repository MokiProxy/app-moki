<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RemoveRiskRankingsTreatmentsAndStrategyFreeText extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE erkap_department_risk_strategies DROP CONSTRAINT IF EXISTS erkap_department_risk_strategies_strategy_check');

        DB::statement('ALTER TABLE erkap_department_risk_strategies ALTER COLUMN strategy TYPE TEXT');

        Schema::dropIfExists('erkap_risk_rankings');
        Schema::dropIfExists('erkap_risk_treatments');
    }

    public function down()
    {
        Schema::create('erkap_risk_rankings', function ($table) {
            $table->id();
            $table->foreignId('erkap_risk_identification_id')->constrained('erkap_risk_identifications')->cascadeOnDelete();
            $table->unsignedInteger('ranking');
            $table->timestamps();
        });

        Schema::create('erkap_risk_treatments', function ($table) {
            $table->id();
            $table->foreignId('erkap_risk_identification_id')->constrained('erkap_risk_identifications')->cascadeOnDelete();
            $table->foreignId('erkap_department_risk_strategy_id')->nullable()->constrained('erkap_department_risk_strategies')->nullOnDelete();
            $table->string('treatment_type');
            $table->text('description')->nullable();
            $table->string('responsible_party')->nullable();
            $table->date('target_date')->nullable();
            $table->string('status')->default('planned');
            $table->text('result')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('ALTER TABLE erkap_department_risk_strategies ALTER COLUMN strategy TYPE VARCHAR(10)');
        DB::statement("ALTER TABLE erkap_department_risk_strategies ADD CONSTRAINT erkap_department_risk_strategies_strategy_check CHECK (strategy IN ('avoid', 'reduce', 'transfer', 'accept'))");
    }
}