<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_pegawai_hirarki', function (Blueprint $table) {
            $table->string('position_id')->primary();
            for ($i = 1; $i <= 8; $i++) {
                $table->string("superior_{$i}")->nullable();
            }
            $table->timestamps();

            for ($i = 1; $i <= 8; $i++) {
                $table->foreign("superior_{$i}")
                    ->references('position_id')
                    ->on('pegawai_master_posisi')
                    ->cascadeOnDelete();
            }

            $table->foreign('position_id')
                ->references('position_id')
                ->on('pegawai_master_posisi')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_pegawai_hirarki');
    }
};
