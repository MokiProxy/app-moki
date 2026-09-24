<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erkap_risk_treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('erkap_risk_identification_id')->constrained('erkap_risk_identifications')->cascadeOnDelete();
            $table->foreignId('erkap_department_risk_strategy_id')->nullable()->constrained('erkap_department_risk_strategies')->nullOnDelete();
            $table->string('treatment_type'); // avoid, mitigate, transfer, accept
            $table->text('description');
            $table->string('responsible_party');
            $table->date('target_date');
            $table->enum('status', ['planned', 'in_progress', 'completed', 'cancelled'])->default('planned');
            $table->text('result')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['erkap_risk_identification_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erkap_risk_treatments');
    }
};
