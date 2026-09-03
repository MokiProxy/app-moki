<?php

use App\Http\Controllers\EQTax\DashboardController;
use App\Http\Controllers\EQTax\EqualizationController;
use App\Http\Controllers\EQTax\GLController;
use App\Http\Controllers\EQTax\SPTCoretaxController;
use App\Http\Controllers\EQTax\TBController;
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
            Route::post("update-field", [SPTCoretaxController::class, "updateField"])->name("update-field");
        });
    });

    Route::prefix('gl')->name('gl.')->group(function () {
        Route::get("/", [GLController::class, "index"])->name("index");
        Route::post("import", [GLController::class, "import"])->name("import");
        Route::post("update-field", [GLController::class, "updateField"])->name("update-field");
    });

    Route::prefix('equalization')->name('equalization.')->group(function () {
        Route::get("/", [EqualizationController::class, "index"])->name("index");
        Route::post("/process", [EqualizationController::class, "equalization"])->name("process");
        Route::get("/export", [EqualizationController::class, "export"])->name("export");
    });

    Route::prefix('tb')->name('tb.')->group(function () {
        Route::get("/", [TBController::class, "index"])->name("index");
        Route::post("/process", [TBController::class, "process"])->name("process");
        Route::post("/save", [TBController::class, "save"])->name("save");
    });
});
