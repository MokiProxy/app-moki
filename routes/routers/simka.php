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
        Route::post("/kalkulasi/pekerjaan-tanah/tarif-gali", [SPPH773Controller::class, 'simpanTarifGaliTanah'])->name("tarif-gali-tanah.simpan");
        Route::post("/kalkulasi/pekerjaan-tanah/tarif-gali/kalkulasi", [SPPH773Controller::class, 'kalkulasiTarifGaliTanah'])->name("tarif-gali-tanah.kalkulasi");
        Route::delete("/kalkulasi/pekerjaan-tanah/tarif-gali", [SPPH773Controller::class, 'hapusTarifGaliTanah'])->name("tarif-gali-tanah.hapus");
        Route::get("/kalkulasi/pekerjaan-tanah/tarif-angkutan", [SPPH773Controller::class, 'tarifAngkutanTanah'])->name("tarif-angkutan-tanah");
        Route::post("/kalkulasi/pekerjaan-tanah/tarif-angkutan", [SPPH773Controller::class, 'simpanTarifAngkutanTanah'])->name("tarif-angkutan-tanah.simpan");
        Route::post("/kalkulasi/pekerjaan-tanah/tarif-angkutan/kalkulasi", [SPPH773Controller::class, 'kalkulasiTarifAngkutanTanah'])->name("tarif-angkutan-tanah.kalkulasi");
        Route::delete("/kalkulasi/pekerjaan-tanah/tarif-angkutan", [SPPH773Controller::class, 'hapusTarifAngkutanTanah'])->name("tarif-angkutan-tanah.hapus");
        Route::get("/kalkulasi/pekerjaan-tanah/tarif-sebar", [SPPH773Controller::class, 'tarifSebarTanah'])->name("tarif-sebar-tanah");
        Route::post("/kalkulasi/pekerjaan-tanah/tarif-sebar", [SPPH773Controller::class, 'simpanTarifSebarTanah'])->name("tarif-sebar-tanah.simpan");
        Route::post("/kalkulasi/pekerjaan-tanah/tarif-sebar/kalkulasi", [SPPH773Controller::class, 'kalkulasiTarifSebarTanah'])->name("tarif-sebar-tanah.kalkulasi");
        Route::delete("/kalkulasi/pekerjaan-tanah/tarif-sebar", [SPPH773Controller::class, 'hapusTarifSebarTanah'])->name("tarif-sebar-tanah.hapus");
        // Tarif Total - Pekerjaan Tanah (view only, logika kalkulasi belum tersedia)
        Route::get("/kalkulasi/pekerjaan-tanah/tarif-total", [SPPH773Controller::class, 'tarifTotalTanah'])->name("tarif-total-tanah");
        Route::post("/kalkulasi/pekerjaan-tanah/tarif-total/kalkulasi", [SPPH773Controller::class, 'placeholderAksi'])->name("tarif-total-tanah.kalkulasi");
        Route::delete("/kalkulasi/pekerjaan-tanah/tarif-total", [SPPH773Controller::class, 'placeholderAksi'])->name("tarif-total-tanah.hapus");
        Route::get("/kalkulasi/pekerjaan-batubara/tarif-gali", [SPPH773Controller::class, 'tarifGaliBatubara'])->name("tarif-gali-batubara");
        Route::post("/kalkulasi/pekerjaan-batubara/tarif-gali", [SPPH773Controller::class, 'simpanTarifGaliBatubara'])->name("tarif-gali-batubara.simpan");
        Route::post("/kalkulasi/pekerjaan-batubara/tarif-gali/kalkulasi", [SPPH773Controller::class, 'kalkulasiTarifGaliBatubara'])->name("tarif-gali-batubara.kalkulasi");
        Route::delete("/kalkulasi/pekerjaan-batubara/tarif-gali", [SPPH773Controller::class, 'hapusTarifGaliBatubara'])->name("tarif-gali-batubara.hapus");
        Route::get("/kalkulasi/pekerjaan-batubara/tarif-angkutan", [SPPH773Controller::class, 'tarifAngkutanBatubara'])->name("tarif-angkutan-batubara");
        Route::post("/kalkulasi/pekerjaan-batubara/tarif-angkutan", [SPPH773Controller::class, 'simpanTarifAngkutanBatubara'])->name("tarif-angkutan-batubara.simpan");
        Route::post("/kalkulasi/pekerjaan-batubara/tarif-angkutan/kalkulasi", [SPPH773Controller::class, 'kalkulasiTarifAngkutanBatubara'])->name("tarif-angkutan-batubara.kalkulasi");
        Route::delete("/kalkulasi/pekerjaan-batubara/tarif-angkutan", [SPPH773Controller::class, 'hapusTarifAngkutanBatubara'])->name("tarif-angkutan-batubara.hapus");
        // Tarif Total - Pekerjaan Batubara (view only, logika kalkulasi belum tersedia)
        Route::get("/kalkulasi/pekerjaan-batubara/tarif-total", [SPPH773Controller::class, 'tarifTotalBatubara'])->name("tarif-total-batubara");
        Route::post("/kalkulasi/pekerjaan-batubara/tarif-total/kalkulasi", [SPPH773Controller::class, 'placeholderAksi'])->name("tarif-total-batubara.kalkulasi");
        Route::delete("/kalkulasi/pekerjaan-batubara/tarif-total", [SPPH773Controller::class, 'placeholderAksi'])->name("tarif-total-batubara.hapus");
    });

    Route::prefix("spph-2287")->name("spph2287.")->group(function () {
        Route::get("/", [SPPH2287Controller::class, 'index'])->name("index");
        Route::get("/konfigurasi-data", [SPPH2287Controller::class, 'konfigurasi'])->name("konfigurasi");
        Route::post("/konfigurasi-data", [SPPH2287Controller::class, 'simpanKonfigurasi'])->name("konfigurasi.simpan");
        Route::get("/kalkulasi", [SPPH2287Controller::class, 'kalkulasi'])->name("kalkulasi");
        // Sub-menu kalkulasi.
        // Seluruh sub-menu memakai aksi generik yang sama (tarifSubMenu / simpanTarif /
        // kalkulasiTarif / hapusTarif) karena SPPH 2287 memakai satu formula kalkulasi.
        foreach ([
            ['pekerjaan-tanah/tarif-gali', 'tarif-gali-tanah'],
            ['pekerjaan-tanah/tarif-angkutan', 'tarif-angkutan-tanah'],
            ['pekerjaan-tanah/tarif-sebar', 'tarif-sebar-tanah'],
            ['pekerjaan-batubara/tarif-gali', 'tarif-gali-batubara'],
            ['pekerjaan-batubara/tarif-angkutan', 'tarif-angkutan-batubara'],
            ['pekerjaan-lingkungan', 'pekerjaan-lingkungan'],
            ['reklamasi-pemeliharaan-tanaman', 'reklamasi-pemeliharaan-tanaman'],
            ['management-stockpile', 'management-stockpile'],
            ['perawatan-jalan-stockpile-iup', 'perawatan-jalan-stockpile-iup'],
        ] as [$path, $name]) {
            Route::get("/kalkulasi/{$path}", [SPPH2287Controller::class, 'tarifSubMenu'])->name($name);
            Route::post("/kalkulasi/{$path}", [SPPH2287Controller::class, 'simpanTarif'])->name("{$name}.simpan");
            Route::post("/kalkulasi/{$path}/kalkulasi", [SPPH2287Controller::class, 'kalkulasiTarif'])->name("{$name}.kalkulasi");
            Route::delete("/kalkulasi/{$path}", [SPPH2287Controller::class, 'hapusTarif'])->name("{$name}.hapus");
        }

        // Tarif Total (view only, logika kalkulasi belum tersedia)
        Route::get("/kalkulasi/pekerjaan-tanah/tarif-total", [SPPH2287Controller::class, 'tarifTotalTanah'])->name("tarif-total-tanah");
        Route::post("/kalkulasi/pekerjaan-tanah/tarif-total/kalkulasi", [SPPH2287Controller::class, 'placeholderAksi'])->name("tarif-total-tanah.kalkulasi");
        Route::delete("/kalkulasi/pekerjaan-tanah/tarif-total", [SPPH2287Controller::class, 'placeholderAksi'])->name("tarif-total-tanah.hapus");
        Route::get("/kalkulasi/pekerjaan-batubara/tarif-total", [SPPH2287Controller::class, 'tarifTotalBatubara'])->name("tarif-total-batubara");
        Route::post("/kalkulasi/pekerjaan-batubara/tarif-total/kalkulasi", [SPPH2287Controller::class, 'placeholderAksi'])->name("tarif-total-batubara.kalkulasi");
        Route::delete("/kalkulasi/pekerjaan-batubara/tarif-total", [SPPH2287Controller::class, 'placeholderAksi'])->name("tarif-total-batubara.hapus");
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
