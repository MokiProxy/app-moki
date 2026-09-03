<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMultiFormatColumnsToEqtaxCoretaxSptTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('eqtax_coretax_spt', function (Blueprint $table) {
            $table->string('kode_transaksi')->nullable()->after('tahun');
            $table->string('esign_status')->nullable()->after('status_faktur');
            $table->string('penandatangan')->nullable()->after('ppnbm');
            $table->string('metode_input')->nullable()->after('referensi');
            $table->string('uraian')->nullable()->after('metode_input');
            $table->boolean('is_show_clear_name')->nullable()->after('uraian');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('eqtax_coretax_spt', function (Blueprint $table) {
            $table->dropColumn([
                'kode_transaksi',
                'esign_status',
                'penandatangan',
                'metode_input',
                'uraian',
                'is_show_clear_name',
            ]);
        });
    }
}
