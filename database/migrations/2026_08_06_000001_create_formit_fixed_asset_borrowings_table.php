<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formit_fixed_asset_borrowings', function (Blueprint $table) {
            $table->id();

            // Data Pemohon (otomatis dari auth user)
            $table->string('pemohon_id')->index();
            $table->string('pemohon_name');
            $table->string('pemohon_jabatan');
            $table->string('pemohon_departemen')->nullable();
            $table->string('pemohon_area')->nullable();

            // Data Pengajuan
            $table->date('date_start');
            $table->date('date_end');
            $table->string('tujuan_lokasi');
            $table->text('keperluan');
            $table->string('tipe_perangkat');

            // Data Penyerahan (diisi oleh Approver)
            $table->string('penyerahkan_name')->nullable();
            $table->string('penyerahkan_jabatan')->nullable();
            $table->string('penyerahkan_departemen')->nullable();
            $table->string('penyerahkan_area')->nullable();

            // Status & Approval
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('rejected_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            // Approver info
            $table->string('approver_id')->nullable()->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formit_fixed_asset_borrowings');
    }
};
