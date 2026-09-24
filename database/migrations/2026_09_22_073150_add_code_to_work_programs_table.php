<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erkap_work_programs', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->after('id');
        });

        // Add unique constraint after populating data (in a separate step or via seeder)
        // For now, we'll add it as nullable and handle uniqueness in application logic
    }

    public function down(): void
    {
        Schema::table('erkap_work_programs', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
