<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source')->default('scanner');
            $table->string('event');
            $table->string('status')->default('info');
            $table->string('filename')->nullable();
            $table->string('extension')->nullable();
            $table->unsignedBigInteger('document_type_id')->nullable();
            $table->string('document_type_name')->nullable();
            $table->string('document_number')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('s3_filename')->nullable();
            $table->string('ftp_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('processing_time_ms')->nullable();
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('document_type_id')
                ->references('id')
                ->on('document_types')
                ->nullOnDelete();

            $table->index('created_at');
            $table->index('status');
            $table->index('event');
            $table->index('document_type_id');
            $table->index('filename');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_logs');
    }
};
