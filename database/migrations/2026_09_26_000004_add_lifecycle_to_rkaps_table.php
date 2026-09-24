<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erkap_rkap', function (Blueprint $table) {
            $table->enum('phase', [
                'initiation', 'preparation', 'consolidation', 'finalization', 'approved', 'archived',
            ])->default('initiation')->after('status');
            $table->timestamp('phase_started_at')->nullable()->after('phase');
            $table->date('kickoff_date')->nullable()->after('phase_started_at');
            $table->text('kickoff_notes')->nullable()->after('kickoff_date');
            $table->string('direction_file_path')->nullable()->after('kickoff_notes');
            $table->text('direction_notes')->nullable()->after('direction_file_path');
            $table->enum('bmi_alignment_status', ['none', 'in_review', 'aligned', 'rejected'])
                ->default('none')->after('direction_notes');
            $table->text('bmi_notes')->nullable()->after('bmi_alignment_status');
            $table->date('resolution_date')->nullable()->after('bmi_notes');
            $table->enum('distribution_status', ['not_distributed', 'distributed'])
                ->default('not_distributed')->after('resolution_date');
        });
    }

    public function down(): void
    {
        Schema::table('erkap_rkap', function (Blueprint $table) {
            $table->dropColumn([
                'phase',
                'phase_started_at',
                'kickoff_date',
                'kickoff_notes',
                'direction_file_path',
                'direction_notes',
                'bmi_alignment_status',
                'bmi_notes',
                'resolution_date',
                'distribution_status',
            ]);
        });
    }
};