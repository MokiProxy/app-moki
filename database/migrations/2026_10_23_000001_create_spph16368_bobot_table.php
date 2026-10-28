<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('simka_spph16368_bobot', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->foreignId('kategori_pendapatan_id')->constrained('simka_kategori_pendapatan');
            $table->decimal('bobot_bbm', 10, 2);
            $table->decimal('bobot_umsp', 10, 2);
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
        Schema::dropIfExists('simka_spph16368_bobot');
    }
};
