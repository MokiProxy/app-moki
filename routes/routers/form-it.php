<?php

use App\Http\Controllers\FormIT\ApprovalController;
use App\Http\Controllers\FormIT\DashboardController;
use App\Http\Controllers\FormIT\FormController;
use Illuminate\Support\Facades\Route;

Route::prefix("form-it")->name("form-it.")->group(function () {
    Route::get("/", [DashboardController::class, 'index'])
        ->name("index")
        ->middleware('permission:form-it.dashboard');

    Route::prefix("forms")->name("forms.")->group(function () {
        Route::get("my-submissions", [FormController::class, "mySubmissions"])
            ->name("my-submissions")
            ->middleware('permission:form-it.forms.view');

        // PENGAJUAN INSTALL SOFTWARE & APLIKASI
        Route::get("software-installation", [FormController::class, "softwareInstallation"])
            ->name("software-installation")
            ->middleware('permission:form-it.forms.create');
        Route::post("software-installation", [FormController::class, "softwareInstallationCreate"])
            ->name("software-installation.create")
            ->middleware('permission:form-it.forms.create');
        Route::get("software-installation/{id}", [FormController::class, "softwareInstallationShow"])
            ->name("software-installation.show")
            ->middleware('permission:form-it.forms.view');
        Route::get("software-installation/{id}/pdf", [FormController::class, "showPdf"])
            ->name("software-installation.pdf")
            ->middleware('permission:form-it.forms.view');

        // PEMINJAMAN FIXED ASSET IT
        Route::prefix("fixed-asset")->name("fixed-asset.")->group(function () {
            Route::get("create", [FormController::class, "fixedAssetCreate"])
                ->name("create")
                ->middleware('permission:form-it.fixed-asset.create');

            Route::post("store", [FormController::class, "fixedAssetStore"])
                ->name("store")
                ->middleware('permission:form-it.fixed-asset.create');

            Route::get("my-submissions", [FormController::class, "fixedAssetMySubmissions"])
                ->name("my-submissions")
                ->middleware('permission:form-it.fixed-asset.view');

            Route::get("{id}", [FormController::class, "fixedAssetShow"])
                ->name("show")
                ->middleware('permission:form-it.fixed-asset.view');

            Route::get("fixed-asset/{id}/pdf", [FormController::class, "fixedAssetShowPdf"])
                ->name("pdf")
                ->middleware('permission:form-it.forms.view');
        });
    });

    // APPROVAL FIXED ASSET (permission check only, no approver middleware)
    Route::prefix("approval/fixed-asset")->name("approval.fixed-asset.")->group(function () {
        Route::get("/", [ApprovalController::class, "fixedAssetIndex"])
            ->name("index")
            ->middleware('permission:form-it.fixed-asset.approve');

        Route::get("/{id}", [ApprovalController::class, "fixedAssetShow"])
            ->name("show")
            ->middleware('permission:form-it.fixed-asset.approve');

        Route::post("/{id}/process", [ApprovalController::class, "fixedAssetProcess"])
            ->name("process")
            ->middleware('permission:form-it.fixed-asset.approve');
    });

    // APPROVAL SOFTWARE INSTALLATION (requires approver middleware)
    Route::prefix("approval")->name("approval.")->middleware(['approver'])->group(function () {
        Route::get("/", [ApprovalController::class, "index"])
            ->name("index")
            ->middleware('permission:form-it.approval.view');
        Route::get("/{id}", [ApprovalController::class, "show"])
            ->name("show")
            ->middleware('permission:form-it.approval.view');
        Route::post("/{id}/process", [ApprovalController::class, "process"])
            ->name("process")
            ->middleware('permission:form-it.approval.process');
    });
});
