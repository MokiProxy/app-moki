<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // investment_plans.cost_center_id -> cost_centers.id (column + FK missing)
        if (!Schema::hasColumn('erkap_investment_plans', 'cost_center_id')) {
            Schema::table('erkap_investment_plans', function (Blueprint $table) {
                $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->cascadeOnDelete()->after('erkap_work_program_id');
            });
        }
    }

    public function down(): void
    {
        // Drop column + FK on investment_plans
        if (Schema::hasColumn('erkap_investment_plans', 'cost_center_id')) {
            Schema::table('erkap_investment_plans', function (Blueprint $table) {
                $table->dropForeign(['cost_center_id']);
                $table->dropColumn('cost_center_id');
            });
        }
    }
};
