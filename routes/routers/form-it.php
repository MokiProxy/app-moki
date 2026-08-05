<?php

use App\Http\Controllers\FormIT\ApprovalController;
use App\Http\Controllers\FormIT\DashboardController;
use App\Http\Controllers\FormIT\FormController;
use Illuminate\Support\Facades\Route;

Route::prefix("form-it")->name("form-it.")->middleware(['permission:helpdesk.dashboard'])->group(function() {
    Route::get("/", [DashboardController::class, 'index'])->name("index");

    Route::prefix("forms")->name("forms.")->group(function() {
        Route::get("my-submissions", [FormController::class, "mySubmissions"])->name("my-submissions");
        Route::get("software-installation", [FormController::class, "softwareInstallation"])->name("software-installation");
        Route::post("software-installation", [FormController::class, "softwareInstallationCreate"])->name("software-installation.create");
        Route::get("software-installation/{id}", [FormController::class, "softwareInstallationShow"])->name("software-installation.show");
        Route::get("software-installation/{id}/pdf", [FormController::class, "showPdf"])->name("software-installation.pdf");
    });

    Route::prefix("approval")->name("approval.")->middleware(['approver'])->group(function() {
        Route::get("/", [ApprovalController::class, "index"])->name("index");
        Route::get("/{id}", [ApprovalController::class, "show"])->name("show");
        Route::post("/{id}/process", [ApprovalController::class, "process"])->name("process");
    });
});
