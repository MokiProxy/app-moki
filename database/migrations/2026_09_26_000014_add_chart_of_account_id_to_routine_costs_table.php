<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erkap_routine_costs', function (Blueprint $table) {
            if (! Schema::hasColumn('erkap_routine_costs', 'chart_of_account_id')) {
                $table->foreignId('chart_of_account_id')
                    ->nullable()
                    ->after('erkap_cost_element_id')
                    ->constrained('chart_of_accounts');
            }
        });
    }

    public function down(): void
    {
        Schema::table('erkap_routine_costs', function (Blueprint $table) {
            if (Schema::hasColumn('erkap_routine_costs', 'chart_of_account_id')) {
                $table->dropForeign(['chart_of_account_id']);
                $table->dropColumn('chart_of_account_id');
            }
        });
    }
};