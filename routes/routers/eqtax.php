<?php

use App\Http\Controllers\EQTax\DashboardController;
use App\Http\Controllers\EQTax\EqualizationController;
use App\Http\Controllers\EQTax\GLController;
use App\Http\Controllers\EQTax\SPTCoretaxController;
use Illuminate\Support\Facades\Route;

Route::prefix("eqtax")->name("eqtax.")->group(function () {
    Route::get("/", [DashboardController::class, 'index'])
        ->name("index");

    Route::get('/dashboard/filter-selisih', [DashboardController::class, 'getFilteredData'])
        ->name('dashboard.filter-selisih');

    Route::prefix("spt")->name("spt.")->group(function () {
        Route::prefix("coretax")->name("coretax.")->group(function () {
            Route::get("/", [SPTCoretaxController::class, "index"])->name("index");
            Route::post("import", [SPTCoretaxController::class, "import"])->name("import");
        });
    });

    Route::prefix('gl')->name('gl.')->group(function () {
        Route::get("/", [GLController::class, "index"])->name("index");
        Route::post("import", [GLController::class, "import"])->name("import");
    });

    Route::prefix('equalization')->name('equalization.')->group(function () {
        Route::get("/", [EqualizationController::class, "index"])->name("index");
        Route::post("/process", [EqualizationController::class, "equalization"])->name("process");
        Route::post("/reprocess", [EqualizationController::class, "reprocess"])->name("reprocess");
        Route::get("/export", [EqualizationController::class, "export"])->name("export");
    });
});
