<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropEntityFromEqtaxGlTable extends Migration
{
    public function up()
    {
        Schema::table('eqtax_gl', function (Blueprint $table) {
            $table->dropIndex(['entity']);
            $table->dropColumn('entity');
        });
    }

    public function down()
    {
        Schema::table('eqtax_gl', function (Blueprint $table) {
            $table->string('entity')->nullable()->after('sheet');
            $table->index('entity');
        });
    }
}
