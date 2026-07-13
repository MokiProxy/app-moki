<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCostCenterToAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::table('assets', function (Blueprint $table) {
        // Menambahkan field cost_center setelah kolom kategori atau lokasi
        $table->string('cost_center')->nullable()->after('regional_id');
    });
}

public function down()
{
    Schema::table('assets', function (Blueprint $table) {
        $table->dropColumn('cost_center');
    });
}
}