<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewUidToTransactionDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
{
    Schema::table('transaction_details', function (Blueprint $table) {
        // Menambahkan kolom new_uid untuk menyimpan format 001-004...
        $table->string('new_uid')->after('asset_id')->nullable();
    });
}

public function down()
{
    Schema::table('transaction_details', function (Blueprint $table) {
        $table->dropColumn('new_uid');
    });
}
}