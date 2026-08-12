<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->text('gemini_prompt')->nullable()->after('description');
            $table->dropColumn([
                'header_regex',
                'number_regex',
                'number_label',
                'keterangan_regex',
                'keterangan_label',
                'keterangan_enabled',
                'uraian_regex',
                'uraian_label',
                'uraian_enabled',
                'tanggal_regex',
                'tanggal_label',
                'tanggal_enabled',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->string('header_regex')->nullable()->after('description');
            $table->string('number_regex')->nullable()->after('header_regex');
            $table->string('number_label')->default('invoice_number')->after('number_regex');
            $table->string('keterangan_regex')->nullable()->after('number_label');
            $table->string('keterangan_label')->default('keterangan')->after('keterangan_regex');
            $table->boolean('keterangan_enabled')->default(true)->after('keterangan_label');
            $table->string('uraian_regex')->nullable()->after('keterangan_enabled');
            $table->string('uraian_label')->default('uraian')->after('uraian_regex');
            $table->boolean('uraian_enabled')->default(true)->after('uraian_label');
            $table->string('tanggal_regex')->nullable()->after('uraian_enabled');
            $table->string('tanggal_label')->default('tanggal')->after('tanggal_regex');
            $table->boolean('tanggal_enabled')->default(true)->after('tanggal_label');
            $table->dropColumn('gemini_prompt');
        });
    }
};
