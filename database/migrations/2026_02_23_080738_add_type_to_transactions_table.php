<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
  public function up()
{
    Schema::table('transactions', function (Blueprint $table) {
        // Menambahkan kolom type untuk menampung data 'IN' atau 'OUT'
        $table->string('type', 10)->after('note')->nullable(); 
    });
}

public function down()
{
    Schema::table('transactions', function (Blueprint $table) {
        $table->dropColumn('type');
    });
}
}