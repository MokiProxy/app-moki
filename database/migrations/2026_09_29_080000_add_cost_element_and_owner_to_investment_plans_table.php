<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erkap_investment_plans', function (Blueprint $table) {
            $table->unsignedBigInteger('erkap_cost_element_id')->nullable()->after('cost_center_id');
            $table->string('cost_center_owner')->nullable()->after('erkap_cost_element_id');

            $table->foreign('erkap_cost_element_id')
                ->references('id')
                ->on('erkap_cost_elements')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('erkap_investment_plans', function (Blueprint $table) {
            $table->dropForeign(['erkap_cost_element_id']);
            $table->dropColumn(['erkap_cost_element_id', 'cost_center_owner']);
        });
    }
};
