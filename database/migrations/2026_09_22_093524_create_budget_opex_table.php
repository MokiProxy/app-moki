<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erkap_budget_opex', function (Blueprint $table) {
            $table->id();
            $table->foreignId('erkap_rkap_id')->constrained('erkap_rkap');
            $table->foreignId('division_id')->constrained('divisions');
            $table->foreignId('cost_center_id')->constrained('cost_centers');
            $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts');
            $table->decimal('budget_amount', 20, 2)->default(0);
            $table->decimal('realization_amount', 20, 2)->default(0);
            $table->decimal('variance', 20, 2)->default(0);
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['erkap_rkap_id', 'division_id', 'cost_center_id', 'chart_of_account_id'], 'erkap_budget_opex_unique');
            $table->index(['erkap_rkap_id', 'division_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erkap_budget_opex');
    }
};
