<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erkap_risk_identifications', function (Blueprint $table) {
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft')->after('erkap_risk_taxonomy_id');
        });

        DB::table('erkap_risk_identifications')->update(['status' => DB::raw('approval_status')]);
    }

    public function down(): void
    {
        Schema::table('erkap_risk_identifications', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
