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
        $this->call(UserSeeder::class);
        $this->call(CompanySeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(SupplierSeeder::class);
        $this->call(RegionalSeeder::class);
        $this->call(DivisionSeeder::class); // Pindahkan ke atas sebelum Employee

        // 2. Data Dengan Relasi (Dependent)
        $this->call(EmployeeSeeder::class); // Butuh DivisionID
        $this->call(AssetSeeder::class);    // Biasanya butuh Category/Supplier

        // Help Desk
        $this->call(TicketCategorySeeder::class);
        $this->call(TicketPrioritySeeder::class);

        // Hierarchy Organisasi
        $this->call(HierarchySeeder::class);
    }
}
