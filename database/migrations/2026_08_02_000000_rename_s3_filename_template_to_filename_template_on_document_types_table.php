<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE document_types RENAME COLUMN s3_filename_template TO filename_template');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE document_types RENAME COLUMN filename_template TO s3_filename_template');
    }
};
