<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEQTAXGLSTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('eqtax_gl', function (Blueprint $table) {
            $table->id();
            $table->string("sheet")->nullable();
            $table->string("no_supplier")->nullable();
            $table->string("nama_supplier")->nullable();
            $table->string("jurnal_date")->nullable();
            $table->string("jurnal_no")->nullable();
            $table->string("invoice_date")->nullable();
            $table->string("invoice_no")->nullable();
            $table->string("invoice_item")->nullable();
            $table->string("no_faktur_pajak")->nullable();
            $table->float("dpp")->nullable();
            $table->float("ppn")->nullable();
            $table->string("keterangan")->nullable();
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
        Schema::dropIfExists('e_q_t_a_x_g_l_s');
    }
}
