<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erkap_investment_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('erkap_investment_plans', 'chart_of_account_id')) {
                $table->foreignId('chart_of_account_id')
                    ->nullable()
                    ->after('erkap_work_program_id')
                    ->constrained('chart_of_accounts');
            }
        });
    }

    public function down(): void
    {
        Schema::table('erkap_investment_plans', function (Blueprint $table) {
            if (Schema::hasColumn('erkap_investment_plans', 'chart_of_account_id')) {
                $table->dropForeign(['chart_of_account_id']);
                $table->dropColumn('chart_of_account_id');
            }
        });
    }
};