<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_type_vendor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->unique(['document_type_id', 'vendor_id']);
        });

        $vendors = DB::table('vendors')->select('id', 'document_type_id')->get();

        foreach ($vendors as $vendor) {
            DB::table('document_type_vendor')->insert([
                'document_type_id' => $vendor->document_type_id,
                'vendor_id' => $vendor->id,
            ]);
        }

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropForeign(['document_type_id']);
            $table->dropColumn('document_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->unsignedBigInteger('document_type_id');
            $table->foreign('document_type_id')->references('id')->on('document_types')->onDelete('cascade');
        });

        $pivots = DB::table('document_type_vendor')->get();

        foreach ($pivots as $pivot) {
            DB::table('vendors')
                ->where('id', $pivot->vendor_id)
                ->update(['document_type_id' => $pivot->document_type_id]);
        }

        Schema::dropIfExists('document_type_vendor');
    }
};
