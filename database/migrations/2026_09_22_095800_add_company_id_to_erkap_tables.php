<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
        });

        Schema::table('erkap_company_targets', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('erkap_rkap_id');
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
        });

        Schema::table('erkap_rkap', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('year');
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('erkap_company_targets', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        Schema::table('erkap_rkap', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};