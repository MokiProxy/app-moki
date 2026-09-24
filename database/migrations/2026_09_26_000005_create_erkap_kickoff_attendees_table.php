<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erkap_kickoff_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('erkap_rkap_id')->constrained('erkap_rkap')->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->boolean('attended')->default(false);
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erkap_kickoff_attendees');
    }
};