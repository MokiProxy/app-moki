<?php

use App\Http\Controllers\Dokter\DashboardController;
use App\Http\Controllers\Dokter\DocumentTypeController;
use App\Http\Controllers\Dokter\FileManagementController;
use App\Http\Controllers\Dokter\VendorController;
use Illuminate\Support\Facades\Route;

Route::prefix("dokter")->name("dokter.")->middleware(['permission:it-admin.access'])->group(function () {
    Route::get("/", [DashboardController::class, 'index'])->name("index");

    Route::resource('vendors', VendorController::class);
    Route::resource('document-types', DocumentTypeController::class)->except(['show']);

    Route::prefix('file-managements')->name('file-managements.')->group(function () {
        Route::get('/', [FileManagementController::class, 'index'])->name('index');
        Route::get('/view', [FileManagementController::class, 'view'])->name('view');
        Route::get('/download', [FileManagementController::class, 'download'])->name('download')->middleware('permission:file-management.download');
    });
});
