<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = 'risk_business_processes';

        if (Schema::hasColumn($table, 'created_by')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->unsignedBigInteger('created_by')->nullable()->after('id');
            $blueprint->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            $blueprint->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $blueprint->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        $table = 'risk_business_processes';

        if (! Schema::hasColumn($table, 'created_by')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->dropForeign(['created_by']);
            $blueprint->dropForeign(['updated_by']);
            $blueprint->dropColumn(['created_by', 'updated_by']);
        });
    }
};