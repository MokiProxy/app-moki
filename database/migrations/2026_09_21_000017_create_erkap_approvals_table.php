<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erkap_approvals', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('approvalable');
            $table->tinyInteger('level')->default(1);
            $table->string('role')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['approvalable_type', 'approvalable_id', 'status', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erkap_approvals');
    }
};