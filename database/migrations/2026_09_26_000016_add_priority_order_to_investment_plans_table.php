<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erkap_investment_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('erkap_investment_plans', 'priority_order')) {
                $table->unsignedSmallInteger('priority_order')->nullable()->after('is_kumulatif');
            }
        });
    }

    public function down(): void
    {
        Schema::table('erkap_investment_plans', function (Blueprint $table) {
            if (Schema::hasColumn('erkap_investment_plans', 'priority_order')) {
                $table->dropColumn('priority_order');
            }
        });
    }
};
