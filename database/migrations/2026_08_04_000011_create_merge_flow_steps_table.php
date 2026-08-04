<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merge_flow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merge_flow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('order');
            $table->string('link_regex')->nullable();
            $table->string('link_label')->nullable();
            $table->timestamps();

            $table->unique(['merge_flow_id', 'order']);
            $table->unique(['merge_flow_id', 'document_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merge_flow_steps');
    }
};
