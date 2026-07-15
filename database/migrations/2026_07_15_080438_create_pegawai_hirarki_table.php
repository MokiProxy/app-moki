<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pegawai_hirarki', function (Blueprint $table) {
            $table->id();
            $table->string('position_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('nopeg')->nullable();
            $table->string('nama')->nullable();
            $table->string('email')->nullable();
            $table->string('jabatan0')->nullable();
            $table->string('kode_satker');

            for ($i = 1; $i <= 8; $i++) {
                $table->string("superior_{$i}")->nullable();
                $table->string("nik{$i}")->nullable();
                $table->string("nopeg_hier{$i}")->nullable();
                $table->string("nama_hier{$i}")->nullable();
                $table->string("ilinier{$i}")->nullable();
                $table->string("email{$i}")->nullable();
                $table->string("jabatan{$i}")->nullable();
                $table->string("kode_satker{$i}")->nullable();
            }

            $table->timestamps();

            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->cascadeOnDelete();
            $table->foreign('nopeg')
                ->references('nopeg')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('position_id')
                ->references('position_id')
                ->on('pegawai_master_posisi')
                ->cascadeOnDelete();
            $table->foreign('kode_satker')
                ->references('kode_satker')
                ->on('pegawai_satker')
                ->cascadeOnDelete();
            $table->index('position_id');
            $table->index('employee_id');
            $table->index('nopeg');
            $table->index('kode_satker');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawai_hirarki');
    }
};
