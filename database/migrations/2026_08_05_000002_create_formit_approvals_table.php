<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formit_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formit_software_installation_id')->constrained('formit_software_installations')->cascadeOnDelete();
            $table->string('approver_id')->index();
            $table->integer('level');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formit_approvals');
    }
};
