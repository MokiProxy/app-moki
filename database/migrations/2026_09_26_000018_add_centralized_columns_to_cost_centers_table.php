<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCentralizedColumnsToCostCentersTable extends Migration
{
    public function up()
    {
        Schema::table('cost_centers', function (Blueprint $table) {
            $table->boolean('is_centralized')->default(false)->after('is_swakelola');
            $table->unsignedBigInteger('coordinating_division_id')->nullable()->after('is_centralized');
        });

        Schema::table('cost_centers', function (Blueprint $table) {
            $table->foreign('coordinating_division_id')
                ->references('id')
                ->on('divisions')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('cost_centers', function (Blueprint $table) {
            $table->dropForeign(['coordinating_division_id']);
            $table->dropColumn('coordinating_division_id');
            $table->dropColumn('is_centralized');
        });
    }
}