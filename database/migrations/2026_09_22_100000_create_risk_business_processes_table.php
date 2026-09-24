<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_business_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risk_assessment_monthly_id')
                ->constrained('erkap_risk_assessments_monthly')
                ->cascadeOnDelete();
            $table->string('process_name');
            $table->text('description')->nullable();
            $table->string('owner')->nullable();
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->timestamps();

            $table->index(['risk_assessment_monthly_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_business_processes');
    }
};