<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvestmentPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erkap_investment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('erkap_work_program_id')->constrained('erkap_work_programs')->cascadeOnDelete();
            $table->foreignId('erkap_investattion_category_id')->constrained('erkap_investattion_categories')->cascadeOnDelete();
            $table->foreignId('erkap_investation_type_id')->constrained('erkap_investation_types')->cascadeOnDelete();
            $table->foreignId('erkap_investation_criteria_id')->constrained('erkap_investation_criterias')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit');
            $table->float('qty');
            $table->float('unit_price');
            $table->float('jan_plan')->nullable();
            $table->float('feb_plan')->nullable();
            $table->float('mar_plan')->nullable();
            $table->float('apr_plan')->nullable();
            $table->float('may_plan')->nullable();
            $table->float('jun_plan')->nullable();
            $table->float('jul_plan')->nullable();
            $table->float('aug_plan')->nullable();
            $table->float('sep_plan')->nullable();
            $table->float('oct_plan')->nullable();
            $table->float('nov_plan')->nullable();
            $table->float('dec_plan')->nullable();
            $table->float('total');
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
        Schema::dropIfExists('erkap_investment_plans');
    }
}
