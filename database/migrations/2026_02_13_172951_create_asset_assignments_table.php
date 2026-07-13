<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            
            // Relasi dengan table assets
            // Menggunakan constrained() akan otomatis mencari table 'assets'
            // onDelete('cascade') artinya jika asset dihapus, data penugasan ini juga ikut terhapus
            $table->foreignId('asset_id')
                  ->constrained('assets')
                  ->onDelete('cascade');

            $table->foreignId('employee_id')->nullable(); 
            
            // Field Data Karyawan & Penugasan
            $table->string('employee_name');
            $table->string('job_title');
            $table->string('department');
            $table->string('location');
            $table->date('assignment_date');
            
            // Penambahan field spesifikasi (menggunakan text agar bisa menampung deskripsi panjang)
            $table->text('specification')->nullable();
            
            $table->enum('condition', ['baru', 'bekas']);
            $table->string('document_path')->nullable();
            $table->text('remarks')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
    }
};