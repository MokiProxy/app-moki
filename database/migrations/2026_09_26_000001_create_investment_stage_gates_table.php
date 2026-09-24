<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erkap_investment_stage_gates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('erkap_investment_plan_id')->constrained('erkap_investment_plans')->cascadeOnDelete();
            $table->enum('stage', ['proposal', 'cba', 'aset', 'direksi_keuangan', 'gate_review_bmi']);
            $table->unsignedSmallInteger('stage_order')->default(1);
            $table->enum('status', ['pending', 'approved', 'rejected', 'revised'])->default('pending');
            $table->string('reviewer_role');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('result')->nullable();
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['erkap_investment_plan_id', 'stage_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erkap_investment_stage_gates');
    }
};