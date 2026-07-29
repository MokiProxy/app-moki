<?php

use App\Http\Controllers\ITAdmin\DashboardController;
use App\Http\Controllers\ITAdmin\UserController;
use App\Http\Controllers\ITAdmin\RoleController;
use Illuminate\Support\Facades\Route;

Route::prefix("it-admin")->name("it-admin.")->middleware(['permission:it-admin.access'])->group(function() {
    Route::get("/", [DashboardController::class, 'index'])->name("index");

    Route::prefix("users")->name("users.")->group(function () {
        Route::get("/", [UserController::class, 'index'])->name("index");
        Route::post("/datatable", [UserController::class, 'datatable'])->name("datatable");
        Route::post("/store", [UserController::class, 'store'])->name("store");
        Route::get("/edit/{id}", [UserController::class, 'edit'])->name("edit");
        Route::post("/set-password", [UserController::class, 'setPassword'])->name("set-password");
        Route::delete("/delete/{id}", [UserController::class, 'destroy'])->name("delete");
    });

    Route::prefix("roles")->name("roles.")->group(function () {
        Route::get("/", [RoleController::class, 'index'])->name("index");
        Route::post("/datatable", [RoleController::class, 'datatable'])->name("datatable");
        Route::post("/store", [RoleController::class, 'store'])->name("store");
        Route::get("/edit/{id}", [RoleController::class, 'edit'])->name("edit");
        Route::put("/update/{id}", [RoleController::class, 'update'])->name("update");
        Route::delete("/delete/{id}", [RoleController::class, 'destroy'])->name("delete");
        Route::get("/permissions/{id}", [RoleController::class, 'permissions'])->name("permissions");
        Route::post("/sync-permissions/{id}", [RoleController::class, 'syncPermissions'])->name("sync-permissions");
    });
});
