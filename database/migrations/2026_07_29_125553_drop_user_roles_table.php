<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::dropIfExists('user_roles');
    }

    public function down()
    {
        // Tidak perlu restore — migrasi sekali jalan
    }
};
