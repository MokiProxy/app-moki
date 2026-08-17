<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEQTAXCoretaxSPTSTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('eqtax_coretax_spt', function (Blueprint $table) {
            $table->id();
            $table->string("npwp_penjual")->nullable();
            $table->string("nama_penjual")->nullable();
            $table->string("no_faktur_pajak")->nullable();
            $table->timestamp("tgl_faktur_pajak")->nullable();
            $table->string("masa_pajak")->nullable();
            $table->string("tahun")->nullable();
            $table->string("masa_pajak_pengkreditan")->nullable();
            $table->string("tahun_pajak_pengkreditan")->nullable();
            $table->string("status_faktur")->nullable();
            $table->bigInteger("harga_jual")->nullable();
            $table->bigInteger("dpp")->nullable();
            $table->bigInteger("ppn")->nullable();
            $table->bigInteger("ppnbm")->nullable();
            $table->string("perekam")->nullable();
            $table->string("referensi")->nullable();
            $table->string("no_sp2d")->nullable()->nullable();
            $table->boolean("valid")->nullable();
            $table->boolean("dilaporkan")->nullable();
            $table->boolean("dilaporkan_oleh_penjual")->nullable();
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
        Schema::dropIfExists('e_q_t_a_x_coretax_s_p_t_s');
    }
}
