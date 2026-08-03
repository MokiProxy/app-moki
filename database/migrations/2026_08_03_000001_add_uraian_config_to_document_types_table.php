<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->string('uraian_regex')->nullable()->after('keterangan_enabled');
            $table->string('uraian_label')->default('uraian')->after('uraian_regex');
        });
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn(['uraian_regex', 'uraian_label']);
        });
    }
};
