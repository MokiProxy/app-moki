<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eqtax_tb_data', function (Blueprint $table) {
            $table->id();
            $table->string('period')->nullable();
            $table->string('entity')->nullable();
            $table->double('ppn_tb')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index('period');
            $table->index('entity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eqtax_tb_data');
    }
};
