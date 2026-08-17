<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEqtaxEqualizationResultsTable extends Migration
{
    public function up()
    {
        Schema::create('eqtax_equalization_results', function (Blueprint $table) {
            $table->id();
            $table->string('period')->nullable();
            $table->string('entity')->nullable();
            $table->string('no_faktur_pajak')->nullable();
            $table->string('nama_penjual')->nullable();
            $table->bigInteger('dpp_spt')->nullable();
            $table->double('dpp_gl')->nullable();
            $table->bigInteger('ppn_spt')->nullable();
            $table->double('ppn_gl')->nullable();
            $table->double('selisih_ppn')->nullable();
            $table->string('status')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index('period');
            $table->index('entity');
            $table->index('no_faktur_pajak');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('eqtax_equalization_results');
    }
}
