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
        Schema::create('simka_spph773_komponen_tarif', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_pendapatan_id')->constrained('simka_kategori_pendapatan');
            $table->decimal('usd', 10, 2);
            $table->decimal('rupiah', 10, 2);
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
        Schema::dropIfExists('simka_spph773_komponen_tarif');
    }
};
