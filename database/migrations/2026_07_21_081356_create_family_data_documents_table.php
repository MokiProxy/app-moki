<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFamilyDataDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('family_data_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("family_data_id");
            $table->string("nama_dokumen");
            $table->text("lampiran");
            $table->timestamps();

            $table->foreign('family_data_id')->references('id')->on('family_data')->onDelete('cascade');
            $table->index('family_data_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('family_data_documents');
    }
}
