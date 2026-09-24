<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erkap_rkap', function (Blueprint $table) {
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'revised'])->default('draft');
        });

        Schema::table('erkap_work_programs', function (Blueprint $table) {
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
        });

        Schema::table('erkap_routine_costs', function (Blueprint $table) {
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
        });

        Schema::table('erkap_investment_plans', function (Blueprint $table) {
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
        });
    }

    public function down(): void
    {
        Schema::table('erkap_rkap', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('erkap_work_programs', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('erkap_routine_costs', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('erkap_investment_plans', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};