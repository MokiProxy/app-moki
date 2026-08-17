<?php

use App\Http\Controllers\EQTax\DashboardController;
use App\Http\Controllers\EQTax\EqualizationController;
use App\Http\Controllers\EQTax\GLController;
use App\Http\Controllers\EQTax\SPTCoretaxController;
use Illuminate\Support\Facades\Route;

Route::prefix("eqtax")->name("eqtax.")->group(function () {
    Route::get("/", [DashboardController::class, 'index'])
        ->name("index");

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
    });
});
