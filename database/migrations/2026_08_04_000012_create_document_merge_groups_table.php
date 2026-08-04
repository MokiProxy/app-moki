<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_merge_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merge_flow_id')->constrained();
            $table->string('vendor_name');
            $table->string('root_document_number');
            $table->unsignedSmallInteger('status')->default(0);
            $table->string('final_pdf_path')->nullable();
            $table->timestamp('merged_at')->nullable();
            $table->timestamps();

            $table->unique(['merge_flow_id', 'vendor_name', 'root_document_number']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_merge_groups');
    }
};
