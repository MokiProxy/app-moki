<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kode Pusat Biaya (cost center) lama berformat 15 karakter
     * ([BU][Lokasi][division_id][aktivitas][elemen]) dan tidak dapat di-parse
     * menjadi segmen a..d. Sesuai keputusan implementasi, data Pusat Biaya &
     * Chart of Account lama di-reset dan dibangun ulang dari master segmen baru.
     *
     * Data anggaran yang bergantung pada FK Pusat Biaya/COA ikut dibersihkan;
     * `down()` tidak dapat memulihkannya.
     */
    public function up(): void
    {
        $this->resetCostCenters();

        Schema::table('cost_centers', function (Blueprint $table) {
            $table->foreignId('erkap_business_unit_id')->nullable()->after('code')->constrained('erkap_business_units')->nullOnDelete();
            $table->foreignId('erkap_location_id')->nullable()->after('erkap_business_unit_id')->constrained('erkap_locations')->nullOnDelete();
            $table->foreignId('erkap_management_area_id')->nullable()->after('erkap_location_id')->constrained('erkap_management_areas')->nullOnDelete();
            $table->foreignId('erkap_activity_id')->nullable()->after('erkap_management_area_id')->constrained('erkap_activities')->nullOnDelete();
        });

        // Kode cost center kini komposisi segmen a..d (11 karakter).
        DB::statement('ALTER TABLE cost_centers ALTER COLUMN code TYPE varchar(11)');
    }

    public function down(): void
    {
        Schema::table('cost_centers', function (Blueprint $table) {
            $table->dropForeign(['erkap_activity_id']);
            $table->dropForeign(['erkap_management_area_id']);
            $table->dropForeign(['erkap_location_id']);
            $table->dropForeign(['erkap_business_unit_id']);

            $table->dropColumn([
                'erkap_activity_id',
                'erkap_management_area_id',
                'erkap_location_id',
                'erkap_business_unit_id',
            ]);
        });

        DB::statement('ALTER TABLE cost_centers ALTER COLUMN code TYPE varchar(50)');
    }

    private function resetCostCenters(): void
    {
        // Kolom NOT NULL harus dihapus (budget opex & rencana pendapatan/beban
        // memakai ON DELETE CASCADE, jadi cukup dihapus eksplisit agar deterministik).
        DB::table('erkap_budget_opex')->delete();
        DB::table('erkap_routine_costs')->update(['cost_center_id' => null, 'chart_of_account_id' => null]);
        DB::table('erkap_investment_plans')->update(['cost_center_id' => null, 'chart_of_account_id' => null]);

        DB::table('cost_centers')->delete();
    }
};
