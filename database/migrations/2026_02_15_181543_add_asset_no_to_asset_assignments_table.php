<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAssetNoToAssetAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up() {
    Schema::table('asset_assignments', function (Blueprint $table) {
        $table->string('asset_no')->nullable()->after('asset_id');
    });
}

public function down()
{
    Schema::table('asset_assignments', function (Blueprint $table) {
        // Menghapus kolom jika migration di-rollback
        $table->dropColumn('asset_no');
    });

    }
}
