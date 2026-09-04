<?php

use App\Http\Controllers\EQTax\DashboardController;
use App\Http\Controllers\EQTax\EqualizationController;
use App\Http\Controllers\EQTax\GLController;
use App\Http\Controllers\EQTax\SPTCoretaxController;
use App\Http\Controllers\EQTax\TBController;
use Illuminate\Support\Facades\Route;

Route::prefix("eqtax")->name("eqtax.")->middleware(['permission:eqtax.menu'])->group(function () {
    Route::get("/", [DashboardController::class, 'index'])
        ->name("index")
        ->middleware('permission:eqtax.dashboard');

    Route::get('/dashboard/filter-selisih', [DashboardController::class, 'getFilteredData'])
        ->name('dashboard.filter-selisih')
        ->middleware('permission:eqtax.dashboard');

    Route::prefix("spt")->name("spt.")->group(function () {
        Route::prefix("coretax")->name("coretax.")->middleware('permission:eqtax.spt.coretax.view')->group(function () {
            Route::get("/", [SPTCoretaxController::class, "index"])->name("index");
            Route::post("import", [SPTCoretaxController::class, "import"])->name("import")->middleware('permission:eqtax.spt.coretax.import');
            Route::post("update-field", [SPTCoretaxController::class, "updateField"])->name("update-field")->middleware('permission:eqtax.spt.coretax.update-field');
        });
    });

    Route::prefix('gl')->name('gl.')->middleware('permission:eqtax.gl.view')->group(function () {
        Route::get("/", [GLController::class, "index"])->name("index");
        Route::post("import", [GLController::class, "import"])->name("import")->middleware('permission:eqtax.gl.import');
        Route::post("update-field", [GLController::class, "updateField"])->name("update-field")->middleware('permission:eqtax.gl.update-field');
    });

    Route::prefix('equalization')->name('equalization.')->middleware('permission:eqtax.equalization.view')->group(function () {
        Route::get("/", [EqualizationController::class, "index"])->name("index");
        Route::post("/process", [EqualizationController::class, "equalization"])->name("process")->middleware('permission:eqtax.equalization.process');
        Route::get("/export", [EqualizationController::class, "export"])->name("export")->middleware('permission:eqtax.equalization.export');
    });

    Route::prefix('tb')->name('tb.')->middleware('permission:eqtax.tb.view')->group(function () {
        Route::get("/", [TBController::class, "index"])->name("index");
        Route::post("/process", [TBController::class, "process"])->name("process")->middleware('permission:eqtax.tb.process');
        Route::post("/save", [TBController::class, "save"])->name("save")->middleware('permission:eqtax.tb.save');
    });
});
