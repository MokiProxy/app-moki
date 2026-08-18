<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEntityToEqtaxCoretaxSptTable extends Migration
{
    public function up()
    {
        Schema::table('eqtax_coretax_spt', function (Blueprint $table) {
            $table->string('entity')->nullable()->after('tahun');
            $table->index('no_faktur_pajak');
        });
    }

    public function down()
    {
        Schema::table('eqtax_coretax_spt', function (Blueprint $table) {
            $table->dropIndex(['no_faktur_pajak']);
            $table->dropColumn('entity');
        });
    }
}
