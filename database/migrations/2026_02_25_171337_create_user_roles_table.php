<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            // Menghubungkan ke tabel employees
            $table->unsignedBigInteger('employee_id');
            $table->string('jabatan')->nullable();
            $table->integer('role_id')->comment('1:Admin, 2:Approver, 3:Staff');
            $table->timestamps();

            // Setup Foreign Key agar data konsisten
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_roles');
    }
};