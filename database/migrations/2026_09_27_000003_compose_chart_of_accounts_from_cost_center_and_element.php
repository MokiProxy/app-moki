<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chart of Account kini adalah hasil komposisi Pusat Biaya (a..d) + Elemen
     * Biaya (e) = 15 karakter, menggantikan kode 16 digit hasil padding legacy.
     * Data COA lama di-reset (lihat 2026_09_27_000002).
     */
    public function up(): void
    {
        $this->resetChartOfAccounts();

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->foreignId('cost_center_id')->nullable()->after('code')->constrained('cost_centers')->restrictOnDelete();
            $table->foreignId('cost_element_id')->nullable()->after('cost_center_id')->constrained('erkap_cost_elements')->restrictOnDelete();
        });

        // Kode COA = a..e (15 karakter).
        DB::statement('ALTER TABLE chart_of_accounts ALTER COLUMN code TYPE varchar(15)');

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX chart_of_accounts_composition_unique
                ON chart_of_accounts (cost_center_id, cost_element_id)
                WHERE cost_center_id IS NOT NULL AND cost_element_id IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS chart_of_accounts_composition_unique');

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropForeign(['cost_element_id']);
            $table->dropForeign(['cost_center_id']);

            $table->dropColumn(['cost_element_id', 'cost_center_id']);
        });

        DB::statement('ALTER TABLE chart_of_accounts ALTER COLUMN code TYPE varchar(16)');
    }

    private function resetChartOfAccounts(): void
    {
        DB::table('erkap_cost_elements')->update(['chart_of_account_id' => null]);
        DB::table('erkap_routine_costs')->update(['chart_of_account_id' => null]);
        DB::table('erkap_investment_plans')->update(['chart_of_account_id' => null]);
        DB::table('erkap_revenue_plans')->delete();
        DB::table('erkap_expense_plans')->delete();

        DB::table('chart_of_accounts')->delete();
    }
};
