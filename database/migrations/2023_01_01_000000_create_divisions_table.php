<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDivisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
{
    Schema::create('divisions', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('code'); // Tambahkan ini
        $table->string('abbreviation');
        $table->foreignId('regional_id');
        $table->foreignId('company_id'); // Tambahkan ini
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
        Schema::dropIfExists('divisions');
    }
}