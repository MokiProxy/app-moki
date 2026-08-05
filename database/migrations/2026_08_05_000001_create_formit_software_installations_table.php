<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formit_software_installations', function (Blueprint $table) {
            $table->id();
            $table->string('pemohon_id')->index();
            $table->string('superior1_id')->nullable()->index();
            $table->string('manager_it_id')->nullable()->index();
            $table->json('softwares');
            $table->text('keterangan')->nullable();
            $table->enum('status', ['pending', 'process', 'approved', 'rejected'])->default('pending');
            $table->string('rejected_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formit_software_installations');
    }
};
