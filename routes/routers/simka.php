<?php

use App\Http\Controllers\SIMKA\DashboardController;
use App\Http\Controllers\SIMKA\SPPH16368Controller;
use App\Http\Controllers\SIMKA\SPPH2287Controller;
use App\Http\Controllers\SIMKA\SPPH2899Controller;
use App\Http\Controllers\SIMKA\SPPH773Controller;
use Illuminate\Support\Facades\Route;

Route::prefix("simka")->name("simka.")->group(function () {
    Route::get("/", [DashboardController::class, 'index'])->name("index");

    Route::prefix("spph-773")->name("spph773.")->group(function () {
        Route::get("/", [SPPH773Controller::class, 'index'])->name("index");
        Route::get("/konfigurasi-data", [SPPH773Controller::class, 'konfigurasi'])->name("konfigurasi");
        Route::post("/konfigurasi-data", [SPPH773Controller::class, 'simpanKonfigurasi'])->name("konfigurasi.simpan");
        Route::get("/kalkulasi", [SPPH773Controller::class, 'kalkulasi'])->name("kalkulasi");
        Route::get("/kalkulasi/pekerjaan-tanah/tarif-gali", [SPPH773Controller::class, 'tarifGaliTanah'])->name("tarif-gali-tanah");
        Route::get("/kalkulasi/pekerjaan-tanah/tarif-angkutan", [SPPH773Controller::class, 'tarifAngkutanTanah'])->name("tarif-angkutan-tanah");
        Route::get("/kalkulasi/pekerjaan-tanah/tarif-sebar", [SPPH773Controller::class, 'tarifSebarTanah'])->name("tarif-sebar-tanah");
        Route::get("/kalkulasi/pekerjaan-batubara/tarif-gali", [SPPH773Controller::class, 'tarifGaliBatubara'])->name("tarif-gali-batubara");
        Route::get("/kalkulasi/pekerjaan-batubara/tarif-angkutan", [SPPH773Controller::class, 'tarifAngkutanBatubara'])->name("tarif-angkutan-batubara");
    });

    Route::prefix("spph-2287")->name("spph2287.")->group(function () {
        Route::get("/", [SPPH2287Controller::class, 'index'])->name("index");
        Route::get("/konfigurasi-data", [SPPH2287Controller::class, 'konfigurasi'])->name("konfigurasi");
        Route::get("/kalkulasi", [SPPH2287Controller::class, 'kalkulasi'])->name("kalkulasi");
    });

    Route::prefix("spph-2899")->name("spph2899.")->group(function () {
        Route::get("/", [SPPH2899Controller::class, 'index'])->name("index");
        Route::get("/konfigurasi-data", [SPPH2899Controller::class, 'konfigurasi'])->name("konfigurasi");
        Route::get("/kalkulasi", [SPPH2899Controller::class, 'kalkulasi'])->name("kalkulasi");
    });

    Route::prefix("spph-16368")->name("spph16368.")->group(function () {
        Route::get("/", [SPPH16368Controller::class, 'index'])->name("index");
        Route::get("/konfigurasi-data", [SPPH16368Controller::class, 'konfigurasi'])->name("konfigurasi");
        Route::get("/kalkulasi", [SPPH16368Controller::class, 'kalkulasi'])->name("kalkulasi");
    });
});
