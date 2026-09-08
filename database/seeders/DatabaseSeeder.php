<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // 1. Master Data Tanpa Relasi (Independent)
        $this->call(CompanySeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(SupplierSeeder::class);
        $this->call(RegionalSeeder::class);
        $this->call(DivisionSeeder::class); // Pindahkan ke atas sebelum Employee

        $this->call(RolePermissionSeeder::class);

        // // 2. Data Dengan Relasi (Dependent)
        $this->call(EmployeeSeeder::class); // Butuh DivisionID
        $this->call(UserSeeder::class);
        $this->call(AssetSeeder::class);    // Biasanya butuh Category/Supplier

        // Help Desk
        $this->call(TicketCategorySeeder::class);
        $this->call(TicketPrioritySeeder::class);

        // Hierarchy Organisasi
        $this->call(HierarchySeeder::class);

        // Master Data Sederhana
        $this->call(GolonganDarahSeeder::class);
        $this->call(JenisKelaminSeeder::class);

        $this->call(WhatsappSeeder::class);

        $this->call(DocumentTypeSeeder::class);
        $this->call(VendorSeeder::class);
        $this->call(MergeFlowSeeder::class);



        // E-RKAP
        $this->call(CostElementCategoriesSeeder::class);
        $this->call(CostElementsSeeder::class);
        $this->call(ErkapRiskAppetiteSeeder::class);
        $this->call(ErkapRiskTaxonomySeeder::class);
        $this->call(ErkapRiskTypeSeeder::class);
        $this->call(ErkapRatingCriteriaSeeder::class);
        $this->call(ErkapRiskScales::class);
        $this->call(ErkapRiskProbabilitiy::class);
        $this->call(ErkapRiskImpact::class);
        $this->call(ErkapRiskScoreLevelSeeder::class);
        $this->call(ErkapInvestationTypeSeeder::class);
        $this->call(ErkapInvestationCriteriaSeeder::class);
        $this->call(ErkapInvestattionCategorySeeder::class);
    }
}
