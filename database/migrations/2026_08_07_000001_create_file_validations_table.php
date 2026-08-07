<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_validations', function (Blueprint $table) {
            $table->id();
            $table->string('file_path')->unique()->index();
            $table->string('file_name');
            $table->string('folder_path')->nullable()->index();
            $table->boolean('is_validated')->default(false);
            $table->string('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->string('unvalidated_by')->nullable();
            $table->timestamp('unvalidated_at')->nullable();
            $table->timestamps();

            $table->index(['folder_path', 'is_validated']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_validations');
    }
};
