<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pegawai_master_posisi', function (Blueprint $table) {
            $table->string('position_id')->primary();
            $table->string('superior_id')->nullable();
            $table->string('pos_title');
            $table->string("kode_jabatan")->nullable();
            $table->date('last_mode_date')->nullable();
            $table->time('last_mode_time')->nullable();
            $table->timestamps();

        });
        Schema::table('pegawai_master_posisi', function (Blueprint $table) {
            $table->foreign('superior_id')
                ->references('position_id')
                ->on('pegawai_master_posisi')
                ->cascadeOnDelete();

                $table->foreign('kode_jabatan')
                ->references('kode_jabatan')
                ->on('jabatan')
                ->cascadeOnDelete();
            $table->index('superior_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawai_master_posisi');
    }
};
