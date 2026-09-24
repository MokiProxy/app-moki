<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddIsSwakelolaToCostCentersTable extends Migration
{
    public function up()
    {
        Schema::table('cost_centers', function (Blueprint $table) {
            $table->boolean('is_swakelola')->default(false)->after('code');
        });

        DB::table('cost_centers')->get(['id', 'code'])->each(function ($row) {
            DB::table('cost_centers')->where('id', $row->id)->update([
                'is_swakelola' => substr($row->code, -7, 3) === '510',
            ]);
        });
    }

    public function down()
    {
        Schema::table('cost_centers', function (Blueprint $table) {
            $table->dropColumn('is_swakelola');
        });
    }
}