<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetsTableFinal extends Migration
{
   public function up()
{
    Schema::create('assets', function (Blueprint $table) {
        $table->id();
        
        // Menggunakan foreignId lebih ringkas dan modern di Laravel
        $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
        $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
        $table->foreignId('regional_id')->nullable()->constrained('regionals')->onDelete('set null');

        // Field pendukung kode otomatis
        $table->string('location_code', 5)->nullable(); 
        $table->string('coa_code', 10)->nullable(); 
        $table->integer('sort_number')->nullable(); 

        // Identitas Barang
        $table->string('uid')->unique(); 
        $table->string('brand')->nullable(); 
        $table->string('serial_number')->unique()->nullable(); 
        $table->text('specification')->nullable();
        $table->year('production_year')->nullable();
        
        // Data Finansial & Kondisi
        $table->date('purchase_date')->nullable();
        $table->bigInteger('purchase_price')->nullable();
        $table->string('condition')->nullable();
        $table->integer('status')->default(0); 
        $table->timestamps();
    });
}
    public function down()
    {
        Schema::dropIfExists('assets');
    }
}