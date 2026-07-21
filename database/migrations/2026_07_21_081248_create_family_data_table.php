<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFamilyDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('family_data', function (Blueprint $table) {
            $table->id();
            $table->string("employee_id");
            $table->string("status_keluarga");
            $table->string("nama_keluarga");
            $table->date("tanggal_lahir");
            $table->char("jenis_kelamin", 1);
            $table->string("status_list");
            $table->string("lampiran")->nullable();
            $table->enum("status_approval", ["ON_PROGRESS", "APPROVED", "REJECTED"]);
            $table->text("catatan")->nullable();
            $table->integer("flag");
            $table->string("lokasi_kerja");
            $table->string("tempat_lahir");
            $table->text("alamat");
            $table->timestamps();

            $table->foreign('employee_id')->references('employee_id')->on('employees')->onDelete('cascade');
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('family_data');
    }
}
