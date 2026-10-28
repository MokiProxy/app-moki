<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function run()
    {
        // Perbaiki data lama yang tersimpan salah karena bug sanitasi
        // Sebelumnya, titik desimal dihapus dari semua input, jadi "37.00" jadi "3700"
        // Nilai bobot persen seharusnya antara 0-100. Jika > 100, kemungkinan besar terkalibrasi salah.
        
        DB::table('simka_spph773_bobot')
            ->where('bobot_bbm', '>', 100)
            ->update(['bobot_bbm' => DB::raw('ROUND(bobot_bbm / 100, 2)')]);
        
        DB::table('simka_spph773_bobot')
            ->where('bobot_umsp', '>', 100)
            ->update(['bobot_umsp' => DB::raw('ROUND(bobot_umsp / 100, 2)')]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Tidak ada aksi reverse, data sudah diperbaiki
    }
};
