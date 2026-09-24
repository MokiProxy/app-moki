<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erkap_department_targets', function (Blueprint $table) {
            $table->smallInteger('priority')->nullable()->after('erkap_rating_criteria_id');
        });
    }

    public function down(): void
    {
        Schema::table('erkap_department_targets', function (Blueprint $table) {
            $table->dropColumn('priority');
        });
    }
};