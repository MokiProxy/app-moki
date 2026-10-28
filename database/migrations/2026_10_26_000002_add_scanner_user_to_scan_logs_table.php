<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scan_logs', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('source')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('scanner_folder')->nullable()->after('user_id');
            $table->string('scanned_by_name')->nullable()->after('scanner_folder');
            $table->index('scanner_folder');
        });
    }

    public function down(): void
    {
        Schema::table('scan_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropIndex(['scanner_folder']);
            $table->dropColumn(['scanner_folder', 'scanned_by_name']);
        });
    }
};
