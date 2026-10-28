<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('simka_spph2287_tarif_total', function (Blueprint $table) {
            $table->id();
            $table->integer('jarak_awal');
            $table->integer('jarak_akhir');
            $table->integer('tarif_total_dasar');
            $table->integer('tarif_total_ekskalasi');
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
        Schema::dropIfExists('simka_spph2287_tarif_total');
    }
};
