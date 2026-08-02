<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->string('keterangan_regex')->nullable()->after('number_label');
            $table->string('keterangan_label')->default('keterangan')->after('keterangan_regex');
        });
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn(['keterangan_regex', 'keterangan_label']);
        });
    }
};
