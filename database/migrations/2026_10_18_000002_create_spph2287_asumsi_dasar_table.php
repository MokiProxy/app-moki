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
        Schema::create('simka_spph2287_asumsi_dasar', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->integer('harga_acuan');
            $table->integer('harga_aktual');
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
        Schema::dropIfExists('simka_spph2287_asumsi_dasar');
    }
};
