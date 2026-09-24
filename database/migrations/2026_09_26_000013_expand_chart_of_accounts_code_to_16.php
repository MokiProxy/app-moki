<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Expand column to hold 16-digit COA codes (raw SQL; doctrine/dbal not installed).
        DB::statement('ALTER TABLE chart_of_accounts ALTER COLUMN code TYPE varchar(16)');

        // Backfill legacy codes (e.g. '6000' -> '6000000000000000').
        // Guard length(code) < 16 so migration is safe to run once; rpad is deterministic
        // and keeps the original digits intact as a prefix.
        DB::statement("UPDATE chart_of_accounts SET code = rpad(code, 16, '0') WHERE length(code) < 16");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE chart_of_accounts ALTER COLUMN code TYPE varchar(10)');
    }
};