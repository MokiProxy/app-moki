<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn(['gemini_prompt', 'gemini_fields']);
        });
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->text('gemini_prompt')->nullable()->after('description');
            $table->json('gemini_fields')->nullable()->after('gemini_prompt');
        });
    }
};
