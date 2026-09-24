<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erkap_report_items', function (Blueprint $table) {
            $table->id();
            $table->string('report_type');
            $table->string('title');
            $table->string('frequency')->default('manual');
            $table->integer('year')->nullable();
            $table->unsignedTinyInteger('month')->nullable();
            $table->string('format')->default('pdf');
            $table->string('file_path')->nullable();
            $table->string('status')->default('success');
            $table->text('error')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erkap_report_items');
    }
};