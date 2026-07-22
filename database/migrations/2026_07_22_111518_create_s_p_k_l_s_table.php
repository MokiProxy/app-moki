<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSPKLSTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('spkl', function (Blueprint $table) {
            $table->id();
            $table->string("nopeg");
            $table->string("nama");
            $table->integer("pp");
            $table->integer("jenis_lembur_id");
            $table->date("tanggal");
            $table->timestamp("checkin");
            $table->timestamp("checkout");
            $table->float("jumlah_jam");
            $table->integer("regional_id");
            $table->string("cost_center");
            $table->string("no_hp");
            $table->text("uraian");
            $table->text("catatan");
            $table->string("meyetujui");
            $table->string("menugaskan");
            $table->string("kode_satker");
            $table->timestamp("validasi_jam_selesai");
            $table->timestamp("validasi_jam_mulai");
            $table->string("created_by");
            $table->string("edited_by");
            $table->date("created_date");
            $table->timestamps();

            $table->foreign('nopeg')->references('nopeg')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('employee_id')->on('employees')->onDelete('cascade');
            $table->foreign('edited_by')->references('employee_id')->on('employees')->onDelete('cascade');
            $table->foreign('kode_satker')->references('kode_satker')->on('pegawai_satker')->onDelete('cascade');
            $table->foreign('regional_id')->references('id')->on('regionals')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('spkl');
    }
}
