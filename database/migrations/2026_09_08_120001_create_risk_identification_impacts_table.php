<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRiskIdentificationImpactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erkap_risk_identification_impacts', function (Blueprint $table) {
            $table->id();
            $table->string('impact');
            $table->foreignId('erkap_risk_identification_id')->constrained('erkap_risk_identifications')->cascadeOnDelete();
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
        Schema::dropIfExists('erkap_risk_identification_impacts');
    }
}
