<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erkap_investment_plans', function (Blueprint $table) {
            $table->string('proposal_file_path')->nullable()->after('is_kumulatif');
            $table->string('proposal_original_name')->nullable()->after('proposal_file_path');
            $table->json('cba_json')->nullable()->after('proposal_original_name');
            $table->string('cba_attachment_path')->nullable()->after('cba_json');
            $table->enum('gate_review_status', ['none', 'in_review', 'partial', 'approved', 'rejected'])->default('none')->after('cba_attachment_path');
        });
    }

    public function down(): void
    {
        Schema::table('erkap_investment_plans', function (Blueprint $table) {
            $table->dropColumn([
                'proposal_file_path',
                'proposal_original_name',
                'cba_json',
                'cba_attachment_path',
                'gate_review_status',
            ]);
        });
    }
};