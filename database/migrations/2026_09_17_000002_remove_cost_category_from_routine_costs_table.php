<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveCostCategoryFromRoutineCostsTable extends Migration
{
    public function up()
    {
        Schema::table('erkap_routine_costs', function (Blueprint $table) {
            $table->dropColumn('cost_category');
        });
    }

    public function down()
    {
        Schema::table('erkap_routine_costs', function (Blueprint $table) {
            $table->enum('cost_category', ['Biaya Umum', 'Bahan Bakar Minyak', 'Sewa Kendaraan'])->nullable();
        });
    }
}
