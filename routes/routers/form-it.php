<?php

use App\Http\Controllers\FormIT\DashboardController;
use App\Http\Controllers\FormIT\FormController;
use Illuminate\Support\Facades\Route;

Route::prefix("form-it")->name("form-it.")->middleware(['permission:helpdesk.dashboard'])->group(function() {
    Route::get("/", [DashboardController::class, 'index'])->name("index");

    Route::prefix("forms")->name("forms.")->group(function() {
        Route::get("software-installation", [FormController::class, "softwareInstallation"])->name("software-installation");
        Route::post("software-installation", [FormController::class, "softwareInstallationCreate"])->name("software-installation.create");
    });
});
