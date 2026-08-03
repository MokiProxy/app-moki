<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->string('tanggal_regex')->nullable()->after('uraian_enabled');
            $table->string('tanggal_label')->default('tanggal')->after('tanggal_regex');
        });
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn(['tanggal_regex', 'tanggal_label']);
        });
    }
};
