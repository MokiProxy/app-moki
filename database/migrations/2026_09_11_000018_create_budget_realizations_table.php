<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBudgetRealizationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erkap_budget_realizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('erkap_rkap_id')->constrained('erkap_rkap')->cascadeOnDelete();
            $table->foreignId('erkap_routine_cost_id')->nullable()->constrained('erkap_routine_costs')->cascadeOnDelete();
            $table->foreignId('erkap_investment_plan_id')->nullable()->constrained('erkap_investment_plans')->cascadeOnDelete();
            $table->tinyInteger('month');
            $table->smallInteger('year');
            $table->decimal('budgeted', 15, 2)->default(0);
            $table->decimal('realized', 15, 2)->default(0);
            $table->decimal('variance', 15, 2)->default(0);
            $table->decimal('variance_percent', 5, 2)->default(0);
            $table->string('source', 50)->default('manual');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['erkap_routine_cost_id', 'erkap_investment_plan_id', 'month', 'year'], 'unique_budget_realization');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('erkap_budget_realizations');
    }
}