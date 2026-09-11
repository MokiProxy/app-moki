<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkProgramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erkap_work_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('erkap_risk_identification_id')->constrained('erkap_risk_identifications')->cascadeOnDelete();
            $table->text("name");
            $table->string("units");
            $table->float("year_plan")->nullable();
            $table->float("jan_plan")->nullable();
            $table->float("feb_plan")->nullable();
            $table->float("mar_plan")->nullable();
            $table->float("apr_plan")->nullable();
            $table->float("may_plan")->nullable();
            $table->float("jun_plan")->nullable();
            $table->float("jul_plan")->nullable();
            $table->float("aug_plan")->nullable();
            $table->float("sep_plan")->nullable();
            $table->float("oct_plan")->nullable();
            $table->float("nov_plan")->nullable();
            $table->float("dec_plan")->nullable();
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
        Schema::dropIfExists('work_programs');
    }
}
