<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExpensePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erkap_expense_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('erkap_rkap_id')->constrained('erkap_rkap')->cascadeOnDelete();
            $table->foreignId('division_id')->constrained('divisions')->cascadeOnDelete();
            $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->decimal('jan_plan', 15, 2)->default(0);
            $table->decimal('feb_plan', 15, 2)->default(0);
            $table->decimal('mar_plan', 15, 2)->default(0);
            $table->decimal('apr_plan', 15, 2)->default(0);
            $table->decimal('may_plan', 15, 2)->default(0);
            $table->decimal('jun_plan', 15, 2)->default(0);
            $table->decimal('jul_plan', 15, 2)->default(0);
            $table->decimal('aug_plan', 15, 2)->default(0);
            $table->decimal('sep_plan', 15, 2)->default(0);
            $table->decimal('oct_plan', 15, 2)->default(0);
            $table->decimal('nov_plan', 15, 2)->default(0);
            $table->decimal('dec_plan', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
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
        Schema::dropIfExists('erkap_expense_plans');
    }
}