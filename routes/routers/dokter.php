<?php

use App\Http\Controllers\Dokter\DashboardController;
use App\Http\Controllers\Dokter\DocumentTypeController;
use App\Http\Controllers\Dokter\FileManagementController;
use App\Http\Controllers\Dokter\LogFileController;
use App\Http\Controllers\Dokter\VendorController;
use Illuminate\Support\Facades\Route;

Route::prefix("dokter")->name("dokter.")->middleware(['permission:dokter.menu'])->group(function () {
    Route::get("/", [DashboardController::class, 'index'])->name("index")->middleware('permission:dokter.dashboard');

    Route::middleware('permission:dokter.vendors.view')->group(function () {
        Route::get('vendors', [VendorController::class, 'index'])->name('vendors.index');
    });
    Route::middleware('permission:dokter.vendors.create')->group(function () {
        Route::get('vendors/create', [VendorController::class, 'create'])->name('vendors.create');
        Route::post('vendors', [VendorController::class, 'store'])->name('vendors.store');
    });
    Route::middleware('permission:dokter.vendors.edit')->group(function () {
        Route::get('vendors/{vendor}/edit', [VendorController::class, 'edit'])->name('vendors.edit');
        Route::put('vendors/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
    });
    Route::middleware('permission:dokter.vendors.delete')->group(function () {
        Route::delete('vendors/{vendor}', [VendorController::class, 'destroy'])->name('vendors.destroy');
    });

    Route::middleware('permission:dokter.document-types.view')->group(function () {
        Route::get('document-types', [DocumentTypeController::class, 'index'])->name('document-types.index');
    });
    Route::middleware('permission:dokter.document-types.create')->group(function () {
        Route::get('document-types/create', [DocumentTypeController::class, 'create'])->name('document-types.create');
        Route::post('document-types', [DocumentTypeController::class, 'store'])->name('document-types.store');
    });
    Route::middleware('permission:dokter.document-types.edit')->group(function () {
        Route::get('document-types/{documentType}/edit', [DocumentTypeController::class, 'edit'])->name('document-types.edit');
        Route::put('document-types/{documentType}', [DocumentTypeController::class, 'update'])->name('document-types.update');
    });
    Route::middleware('permission:dokter.document-types.delete')->group(function () {
        Route::delete('document-types/{documentType}', [DocumentTypeController::class, 'destroy'])->name('document-types.destroy');
    });

    Route::prefix('file-managements')->name('file-managements.')->group(function () {
        Route::middleware('permission:dokter.file-managements.view')->group(function () {
            Route::get('/', [FileManagementController::class, 'index'])->name('index');
            Route::get('/view', [FileManagementController::class, 'view'])->name('view');
        });
        Route::get('/download', [FileManagementController::class, 'download'])->name('download')->middleware('permission:dokter.file-managements.download');
    });

    Route::prefix('log-file')->name('log-file.')->group(function () {
        Route::middleware('permission:dokter.log-file.view')->group(function () {
            Route::get('/', [LogFileController::class, 'index'])->name('index');
        });
        Route::middleware('permission:dokter.log-file.export')->group(function () {
            Route::get('/export', [LogFileController::class, 'export'])->name('export');
        });
    });
});
