<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepartmentTargetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erkap_department_targets', function (Blueprint $table) {
            $table->id();
            $table->text("target");
            $table->foreignId('division_id')->constrained('divisions')->cascadeOnDelete();
            $table->foreignId('erkap_rating_criteria_id')->constrained('erkap_rating_criterias')->cascadeOnDelete();
            $table->foreignId('erkap_company_target_id')->constrained('erkap_company_targets')->cascadeOnDelete();
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
        Schema::dropIfExists('department_targets');
    }
}
