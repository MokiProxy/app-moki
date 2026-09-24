<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erkap_risk_assessments_monthly', function (Blueprint $table) {
            if (! Schema::hasColumn('erkap_risk_assessments_monthly', 'target_date')) {
                $table->date('target_date')->nullable();
            }
            if (! Schema::hasColumn('erkap_risk_assessments_monthly', 'risk_appetite_id')) {
                $table->foreignId('risk_appetite_id')
                    ->nullable()
                    ->constrained('erkap_risk_appetites')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('erkap_risk_assessments_monthly', function (Blueprint $table) {
            $table->dropForeign(['risk_appetite_id']);
            $table->dropColumn('risk_appetite_id');
            $table->dropColumn('target_date');
        });
    }
};