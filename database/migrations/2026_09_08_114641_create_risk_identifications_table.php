<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRiskIdentificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erkap_risk_identifications', function (Blueprint $table) {
            $table->id();
            $table->string("risk");
            $table->foreignId('erkap_department_target_id')->constrained('erkap_department_targets')->cascadeOnDelete();
            $table->foreignId('erkap_risk_type_id')->constrained('erkap_risk_types')->cascadeOnDelete();
            $table->foreignId('erkap_risk_taxonomy_id')->constrained('erkap_risk_taxonomies')->cascadeOnDelete();
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
        Schema::dropIfExists('risk_identifications');
    }
}
