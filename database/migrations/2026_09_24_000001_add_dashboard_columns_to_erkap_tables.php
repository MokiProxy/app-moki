<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erkap_risk_appetites', function (Blueprint $table) {
            $table->unsignedSmallInteger('threshold_score')->nullable()->after('name');
        });

        Schema::table('erkap_work_programs', function (Blueprint $table) {
            $table->foreignId('depends_on_work_program_id')->nullable()
                ->after('erkap_risk_identification_id')
                ->constrained('erkap_work_programs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('erkap_work_programs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('depends_on_work_program_id');
        });

        Schema::table('erkap_risk_appetites', function (Blueprint $table) {
            $table->dropColumn('threshold_score');
        });
    }
};