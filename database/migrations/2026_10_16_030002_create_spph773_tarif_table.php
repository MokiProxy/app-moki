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
        Schema::create('simka_spph773_tarif', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_tarif_id')->constrained('simka_spph773_kategori_tarif');
            $table->integer('jarak_awal');
            $table->integer('jarak_akhir');
            $table->integer('tarif_dasar');
            $table->integer('tarif_adjustment');
            $table->integer('tarif_ekskalasi');
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
        Schema::dropIfExists('simka_spph773_tarif');
    }
};
