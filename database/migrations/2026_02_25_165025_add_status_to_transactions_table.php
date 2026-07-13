<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   // Jalankan: php artisan make:migration add_status_to_transactions_table
public function up()
{
    Schema::table('transactions', function (Blueprint $table) {
        // 1=Pending, 2=Approved, 3=Tolak
        // Kita gunakan tinyInteger karena hanya menyimpan angka kecil (hemat memori)
        $table->tinyInteger('status_approval')->default(1)->after('type'); 
    });
}

public function down()
{
    Schema::table('transactions', function (Blueprint $table) {
        $table->dropColumn('status_approval');
    });
}
}