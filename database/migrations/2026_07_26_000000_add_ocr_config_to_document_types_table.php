<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->string('number_regex')
                ->default('/No\\s+Inv\\s*\\n?\\s*:\\s*(.+)/i')
                ->after('description');
            $table->string('number_label')
                ->default('invoice_number')
                ->after('number_regex');
            $table->string('s3_filename_template')
                ->default('{vendor}_{number}.{ext}')
                ->after('number_label');
            $table->string('ftp_folder_template')
                ->default('{document_type}/{vendor}')
                ->after('s3_filename_template');
            $table->string('ftp_failed_folder')
                ->default('FAILED')
                ->after('ftp_folder_template');
            $table->boolean('vendor_search_enabled')
                ->default(true)
                ->after('ftp_folder_template');
        });
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn([
                'number_regex',
                'number_label',
                's3_filename_template',
                'ftp_folder_template',
                'ftp_failed_folder',
                'ocr_language',
                'vendor_search_enabled',
            ]);
        });
    }
};
