<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('code', 10)->nullable()->unique()->after('id');
            $table->string('short_name')->nullable()->after('abbreviation');
            $table->text('address')->nullable()->after('short_name');
            $table->string('npwp')->nullable()->after('address');
            $table->string('logo_path')->nullable()->after('npwp');
            $table->boolean('is_active')->default(true)->after('logo_path');
            $table->boolean('is_parent')->default(false)->after('is_active');
            $table->foreignId('parent_company_id')->nullable()->after('is_parent');
            $table->softDeletes();

            $table->foreign('parent_company_id')->references('id')->on('companies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['parent_company_id']);
            $table->dropUnique(['code']);
            $table->dropColumn([
                'code',
                'short_name',
                'address',
                'npwp',
                'logo_path',
                'is_active',
                'is_parent',
                'parent_company_id',
                'deleted_at',
            ]);
        });
    }
};