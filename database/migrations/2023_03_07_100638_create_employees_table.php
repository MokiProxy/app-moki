<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::create('employees', function (Blueprint $table) {
        $table->id();
        $table->string('employee_id')->unique();
        $table->string('name');
        $table->string('division_code', 10)->nullable(); // Tambahkan ini
        $table->string('jabatan')->nullable();
        $table->string('email')->nullable();
        $table->string('hp')->nullable();
        $table->text('address')->nullable();
        
        // Foreign Keys
        $table->unsignedBigInteger('division_id');
        $table->unsignedBigInteger('regional_id');
        
        $table->timestamps();

        // Relasi (Pastikan tabel divisions & regionals sudah dibuat sebelumnya)
        $table->foreign('division_id')->references('id')->on('divisions')->onDelete('cascade');
        $table->foreign('regional_id')->references('id')->on('regionals')->onDelete('cascade');
    });
}
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employees');
    }
}