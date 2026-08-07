<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditor_access_links', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('token', 64)->unique()->index();
            $table->string('description')->nullable();
            $table->json('allowed_years');
            $table->boolean('is_active')->default(true);
            $table->string('created_by')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditor_access_links');
    }
};
