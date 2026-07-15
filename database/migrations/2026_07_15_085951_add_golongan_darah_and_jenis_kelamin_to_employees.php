<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('golongan_darah_id')
                ->nullable()
                ->constrained('golongan_darah')
                ->cascadeOnDelete();
            $table->foreignId('jenis_kelamin_id')
                ->nullable()
                ->constrained('jenis_kelamin')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['golongan_darah_id']);
            $table->dropColumn('golongan_darah_id');
            $table->dropForeign(['jenis_kelamin_id']);
            $table->dropColumn('jenis_kelamin_id');
        });
    }
};
