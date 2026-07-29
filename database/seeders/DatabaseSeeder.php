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
    }
}
