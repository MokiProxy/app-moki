<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgramRealizationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erkap_program_realizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('erkap_work_program_id')->constrained('erkap_work_programs')->cascadeOnDelete();
            $table->tinyInteger('month');
            $table->smallInteger('year');
            $table->decimal('target', 15, 2)->default(0);
            $table->decimal('realized', 15, 2)->default(0);
            $table->decimal('percent_complete', 5, 2)->default(0);
            $table->enum('status', ['on_progress', 'done', 'overdue'])->default('on_progress');
            $table->text('notes')->nullable();
            $table->string('evidence_url', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['erkap_work_program_id', 'month', 'year'], 'unique_program_realization');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('erkap_program_realizations');
    }
}