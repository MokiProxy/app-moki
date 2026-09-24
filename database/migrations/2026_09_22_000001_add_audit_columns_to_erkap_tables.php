<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = collect(DB::select(
            "SELECT table_name FROM information_schema.tables
             WHERE table_schema = current_schema()
               AND table_name LIKE 'erkap\_%'
               AND table_name <> 'erkap_audit_logs'"
        ))->pluck('table_name')->values();

        foreach ($tables as $table) {
            $hasCreatedBy = (bool) DB::selectOne(
                "SELECT 1 FROM information_schema.columns
                 WHERE table_schema = current_schema() AND table_name = ? AND column_name = 'created_by'",
                [$table]
            );

            if ($hasCreatedBy) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->unsignedBigInteger('created_by')->nullable();
                $blueprint->unsignedBigInteger('updated_by')->nullable();
                $blueprint->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $blueprint->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        $tables = collect(DB::select(
            "SELECT table_name FROM information_schema.tables
             WHERE table_schema = current_schema()
               AND table_name LIKE 'erkap\_%'
               AND table_name <> 'erkap_audit_logs'"
        ))->pluck('table_name')->values();

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'created_by') || Schema::hasColumn($table, 'updated_by')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn(['created_by', 'updated_by']);
                });
            }
        }
    }
};