<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateErkapZbbReviewsTable extends Migration
{
    public function up()
    {
        Schema::create('erkap_zbb_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('erkap_rkap_id')->constrained('erkap_rkap')->cascadeOnDelete();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->enum('subject_type', ['routine_cost', 'investment_plan', 'revenue_plan', 'work_program']);
            $table->unsignedBigInteger('subject_id');
            $table->string('display_name')->nullable();
            $table->decimal('prior_year_amount', 20, 2)->default(0);
            $table->decimal('proposed_amount', 20, 2)->default(0);
            $table->decimal('delta_amount', 20, 2)->default(0);
            $table->decimal('delta_percent', 8, 2)->default(0);
            $table->text('increase_rationale')->nullable();
            $table->enum('zbb_status', ['pending', 'reviewed', 'approved', 'rejected', 'skipped'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->unique(['erkap_rkap_id', 'subject_type', 'subject_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('erkap_zbb_reviews');
    }
}