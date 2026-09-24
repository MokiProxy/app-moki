<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('erkap_work_schedules');
    }

    public function down(): void
    {
        // Feature removed intentionally; table is not recreated.
    }
};