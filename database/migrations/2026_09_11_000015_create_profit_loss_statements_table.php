<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfitLossStatementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erkap_profit_loss_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('erkap_rkap_id')->constrained('erkap_rkap')->cascadeOnDelete();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->enum('period', ['monthly', 'quarterly', 'yearly'])->default('yearly');
            $table->tinyInteger('month')->nullable();
            $table->tinyInteger('quarter')->nullable();
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('total_expense', 15, 2)->default(0);
            $table->decimal('gross_profit', 15, 2)->default(0);
            $table->decimal('operating_expense', 15, 2)->default(0);
            $table->decimal('operating_profit', 15, 2)->default(0);
            $table->decimal('other_income', 15, 2)->default(0);
            $table->decimal('other_expense', 15, 2)->default(0);
            $table->decimal('profit_before_tax', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('net_profit', 15, 2)->default(0);
            $table->decimal('margin', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('erkap_profit_loss_statements');
    }
}