<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // (a) Bisnis Unit
        Schema::create('erkap_business_units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 1)->unique();
            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['code', 'name']);
        });

        // (b) Lokasi — child dari Bisnis Unit
        Schema::create('erkap_locations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2);
            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->foreignId('erkap_business_unit_id')->nullable()->constrained('erkap_business_units')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['code', 'erkap_business_unit_id'], 'erkap_locations_code_bu_unique');
        });

        // (c) Manajemen Area — child dari Lokasi, dijemputkan ke org chart (division)
        Schema::create('erkap_management_areas', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5);
            $table->string('name', 150);
            $table->string('description')->nullable();
            $table->foreignId('erkap_location_id')->nullable()->constrained('erkap_locations')->nullOnDelete();
            $table->foreignId('erkap_business_unit_id')->nullable()->constrained('erkap_business_units')->nullOnDelete();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['code', 'erkap_location_id'], 'erkap_management_areas_code_location_unique');
        });

        // (d) Aktivitas — child dari Manajemen Area
        Schema::create('erkap_activities', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3);
            $table->string('name', 150);
            $table->string('description')->nullable();
            $table->foreignId('erkap_management_area_id')->nullable()->constrained('erkap_management_areas')->nullOnDelete();
            $table->foreignId('erkap_location_id')->nullable()->constrained('erkap_locations')->nullOnDelete();
            $table->foreignId('erkap_business_unit_id')->nullable()->constrained('erkap_business_units')->nullOnDelete();
            $table->boolean('is_swakelola')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['code', 'erkap_management_area_id'], 'erkap_activities_code_area_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erkap_activities');
        Schema::dropIfExists('erkap_management_areas');
        Schema::dropIfExists('erkap_locations');
        Schema::dropIfExists('erkap_business_units');
    }
};
