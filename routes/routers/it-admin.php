<?php

use App\Http\Controllers\ITAdmin\DashboardController;
use App\Http\Controllers\ITAdmin\UserController;
use App\Http\Controllers\ITAdmin\RoleController;
use Illuminate\Support\Facades\Route;

Route::prefix("it-admin")->name("it-admin.")->middleware(['permission:it-admin.access'])->group(function() {
    Route::get("/", [DashboardController::class, 'index'])->name("index");

    Route::prefix("users")->name("users.")->middleware(['permission:it-admin.users.manage'])->group(function () {
        Route::get("/", [UserController::class, 'index'])->name("index");
        Route::post("/datatable", [UserController::class, 'datatable'])->name("datatable");
        Route::post("/store", [UserController::class, 'store'])->name("store");
        Route::get("/edit/{id}", [UserController::class, 'edit'])->name("edit");
        Route::post("/set-password", [UserController::class, 'setPassword'])->name("set-password");
        Route::delete("/delete/{id}", [UserController::class, 'destroy'])->name("delete");
    });

    Route::prefix("roles")->name("roles.")->middleware(['permission:it-admin.roles.manage'])->group(function () {
        Route::get("/", [RoleController::class, 'index'])->name("index");
        Route::post("/datatable", [RoleController::class, 'datatable'])->name("datatable");
        Route::post("/store", [RoleController::class, 'store'])->name("store");
        Route::get("/edit/{id}", [RoleController::class, 'edit'])->name("edit");
        Route::put("/update/{id}", [RoleController::class, 'update'])->name("update");
        Route::delete("/delete/{id}", [RoleController::class, 'destroy'])->name("delete");
    });

    Route::middleware(['permission:it-admin.permissions.manage'])->group(function () {
        Route::get("/roles/permissions/{id}", [RoleController::class, 'permissions'])->name("roles.permissions");
        Route::post("/roles/sync-permissions/{id}", [RoleController::class, 'syncPermissions'])->name("roles.sync-permissions");
    });
});
