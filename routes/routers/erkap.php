<?php

use App\Http\Controllers\Erkap\DashboardController as ErkapDashboardController;
use App\Http\Controllers\FormIT\ApprovalController;
use App\Http\Controllers\FormIT\DashboardController;
use App\Http\Controllers\FormIT\FormController;
use Illuminate\Support\Facades\Route;

Route::prefix("erkap")->name("erkap.")->group(function () {
    Route::get("/", [ErkapDashboardController::class, 'index'])->name("index");
});
