<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_merge_group_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merge_group_id')->constrained('document_merge_groups')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained();
            $table->foreignId('scan_log_id')->constrained();
            $table->string('document_number');
            $table->unsignedSmallInteger('order');
            $table->string('ftp_path');
            $table->timestamps();

            $table->unique(['merge_group_id', 'document_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_merge_group_items');
    }
};
