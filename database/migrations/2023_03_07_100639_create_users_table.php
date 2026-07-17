<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // Diubah menjadi string agar bisa menampung format NIP string seperti "EMP0001"
            $table->string('employee_id')->nullable();
            $table->string("nopeg")->unique()->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Letakkan role_id di sini
            $table->integer('role_id')->default(3)->comment('1:Superadmin, 2:Admin, 3:Atasan');

            // Hapus foreign key constraint karena tipenya string (NIP), 
            // namun index pencarian tetap dipertahankan untuk mengoptimalkan performa query login
            $table->index('employee_id');
            $table->index('nopeg');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}