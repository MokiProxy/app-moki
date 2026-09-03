<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFormat3ColumnsToEqtaxCoretaxSptTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('eqtax_coretax_spt', function (Blueprint $table) {
            $table->string('jenis_transaksi')->nullable()->after('no_sp2d');
            $table->string('keterangan')->nullable()->after('jenis_transaksi');
            $table->string('dibuat_oleh')->nullable()->after('keterangan');
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
            $table->dropColumn(['jenis_transaksi', 'keterangan', 'dibuat_oleh']);
        });
    }
}
