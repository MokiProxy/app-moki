<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoutineCostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erkap_routine_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('erkap_work_program_id')->constrained('erkap_work_programs')->cascadeOnDelete();
            $table->enum('cost_category', ['Biaya Umum', 'Bahan Bakar Minyak', 'Sewa Kendaraan']);
            $table->text('need');
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->string('cost_center_owner');
            $table->float('qty');
            $table->float('unit_price');
            $table->foreignId('erkap_cost_element_id')->constrained('erkap_cost_elements')->cascadeOnDelete();
            $table->float('jan_cost')->nullable();
            $table->float('feb_cost')->nullable();
            $table->float('mar_cost')->nullable();
            $table->float('apr_cost')->nullable();
            $table->float('may_cost')->nullable();
            $table->float('jun_cost')->nullable();
            $table->float('jul_cost')->nullable();
            $table->float('aug_cost')->nullable();
            $table->float('sep_cost')->nullable();
            $table->float('oct_cost')->nullable();
            $table->float('nov_cost')->nullable();
            $table->float('des_cost')->nullable();
            $table->float('total');
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
        Schema::dropIfExists('erkap_routine_costs');
    }
}
