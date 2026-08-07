<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formit_fixed_asset_device_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_borrowing_id')
                  ->constrained('formit_fixed_asset_borrowings')
                  ->onDelete('cascade');
            $table->string('uraian');
            $table->boolean('ada')->default(false);
            $table->boolean('tidak_ada')->default(false);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formit_fixed_asset_device_completions');
    }
};
