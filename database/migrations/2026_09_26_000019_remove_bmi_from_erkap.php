<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('erkap_investment_stage_gates')
            ->where('stage', 'gate_review_bmi')
            ->delete();

        DB::statement('ALTER TABLE erkap_investment_stage_gates DROP CONSTRAINT IF EXISTS erkap_investment_stage_gates_stage_check');
        DB::statement("ALTER TABLE erkap_investment_stage_gates ADD CONSTRAINT erkap_investment_stage_gates_stage_check CHECK (stage IN ('proposal'::character varying, 'cba'::character varying, 'aset'::character varying, 'direksi_keuangan'::character varying))");

        Schema::table('erkap_rkap', function (Blueprint $table) {
            $table->dropColumn(['bmi_alignment_status', 'bmi_notes']);
        });
    }

    public function down(): void
    {
        Schema::table('erkap_rkap', function (Blueprint $table) {
            $table->enum('bmi_alignment_status', ['none', 'in_review', 'aligned', 'rejected'])
                ->default('none')->after('direction_notes');
            $table->text('bmi_notes')->nullable()->after('bmi_alignment_status');
        });

        DB::statement('ALTER TABLE erkap_investment_stage_gates DROP CONSTRAINT IF EXISTS erkap_investment_stage_gates_stage_check');
        DB::statement("ALTER TABLE erkap_investment_stage_gates ADD CONSTRAINT erkap_investment_stage_gates_stage_check CHECK (stage IN ('proposal'::character varying, 'cba'::character varying, 'aset'::character varying, 'direksi_keuangan'::character varying, 'gate_review_bmi'::character varying))");
    }
};