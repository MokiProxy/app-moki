<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\RegionalController;
use App\Http\Controllers\SelectController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SatuanKerjaController;
use App\Http\Controllers\PegawaiMasterPosisiController;
use App\Http\Controllers\PegawaiHirarkiController;
use App\Http\Controllers\MasterPegawaiHirarkiController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\AssetAssignmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ApprovedController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\WhatsappController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\Dokter\AuditorFileController;
use App\Http\Controllers\Auth\MicrosoftAuthController;
use App\Http\Controllers\TestingController;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- PUBLIC / TEST AREA (Bisa diakses tanpa login) ---
// Route::get('/portal-test', [PortalController::class, 'index'])->name('portal.test');
Route::get('/portal', [PortalController::class, 'index'])->name('portal.index');

Route::get("/testing", [TestingController::class, "test"])->name("testing.index");
Route::post("/testing/excel", [TestingController::class, "excel"])->name("testing.excel");

// --- GUEST AREA (Hanya untuk yang belum login) ---
Route::group(['middleware' => ['guest']], function () {
    Route::get('/', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticated'])->name('login.process');
});

Route::get("/laporan", function() {
    return view("laporan");
});

// --- AUDITOR PUBLIC ACCESS (No Auth Required) ---
Route::get('/auditor/{token}', [AuditorFileController::class, 'index'])->name('auditor.access');
Route::get('/auditor/{token}/view', [AuditorFileController::class, 'view'])->name('auditor.view');
Route::get('/auditor/{token}/download', [AuditorFileController::class, 'download'])->name('auditor.download');

// --- AUTH AREA (Harus login) ---
Route::group(['middleware' => ['auth']], function () {
    require __DIR__.'/routers/it-admin.php';
    require __DIR__.'/routers/helpdesk.php';
    require __DIR__.'/routers/dokter.php';
    require __DIR__.'/routers/form-it.php';
    require __DIR__.'/routers/eqtax.php';

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // PORTAL UTAMA
    Route::get('/dashboard', [PortalController::class, 'index'])->name('dashboard')->middleware('permission:portal.access');

    // DASHBOARD ANALYTIK AMS
    Route::get('/ams/analytics', [DashboardController::class, 'index'])->name('ams.analytics')->middleware('permission:ams.dashboard');

    // WHATSAPP SETTING
    Route::get('/setting-fonnte', [WhatsappController::class, 'index'])->name('setting-fonnte.index')->middleware('permission:ams.whatsapp-settings.manage');
    Route::post('/setting-fonnte/update', [WhatsappController::class, 'update'])->name('setting-fonnte.update')->middleware('permission:ams.whatsapp-settings.manage');

    // EMPLOYEE
    Route::prefix('employee')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('employee')->middleware('permission:ams.employees.view');
        Route::post('/datatable', [EmployeeController::class, 'datatable'])->name('employee.datatable')->middleware('permission:ams.employees.view');
        Route::post('/store', [EmployeeController::class, 'store'])->name('employee.store')->middleware('permission:ams.employees.manage');
        Route::get('/show/{id}', [EmployeeController::class, 'show'])->name('employee.show')->middleware('permission:ams.employees.view');
        Route::put('/update/{id}', [EmployeeController::class, 'update'])->name('employee.update')->middleware('permission:ams.employees.manage');
        Route::delete('/delete/{id}', [EmployeeController::class, 'destroy'])->name('employee.delete')->middleware('permission:ams.employees.manage');
        Route::post('/import', [EmployeeController::class, 'import'])->name('employee.import')->middleware('permission:ams.employees.import');
        Route::get('/template', [EmployeeController::class, 'downloadTemplate'])->name('employee.template')->middleware('permission:ams.employees.import');
    });

    // MASTER DATA
    Route::middleware('permission:ams.master-data.view')->group(function () {
        Route::get('/regional', [RegionalController::class, 'index'])->name('regional');
        Route::post('/regional/datatable', [RegionalController::class, 'datatable'])->name('regional.datatable');

        Route::get('/company', [CompanyController::class, 'index'])->name('company');
        Route::post('/company/datatable', [CompanyController::class, 'datatable'])->name('company.datatable');

        Route::get('/division', [DivisionController::class, 'index'])->name('division');
        Route::post('/division/datatable', [DivisionController::class, 'datatable'])->name('division.datatable');

        Route::get('/satuan-kerja', [SatuanKerjaController::class, 'index'])->name('satuan-kerja');
        Route::post('/satuan-kerja/datatable', [SatuanKerjaController::class, 'datatable'])->name('satuan-kerja.datatable');

        Route::get('/master-posisi', [PegawaiMasterPosisiController::class, 'index'])->name('master-posisi');
        Route::post('/master-posisi/datatable', [PegawaiMasterPosisiController::class, 'datatable'])->name('master-posisi.datatable');

        Route::get('/pegawai-hirarki', [PegawaiHirarkiController::class, 'index'])->name('pegawai-hirarki');
        Route::post('/pegawai-hirarki/datatable', [PegawaiHirarkiController::class, 'datatable'])->name('pegawai-hirarki.datatable');
        Route::get('/pegawai-hirarki/{id}/hierarchy', [PegawaiHirarkiController::class, 'hierarchy'])->name('pegawai-hirarki.hierarchy');

        Route::get('/master-hirarki', [MasterPegawaiHirarkiController::class, 'index'])->name('master-hirarki');
        Route::post('/master-hirarki/datatable', [MasterPegawaiHirarkiController::class, 'datatable'])->name('master-hirarki.datatable');

        Route::get('/category', [CategoryController::class, 'index'])->name('category');
        Route::post('/category/datatable', [CategoryController::class, 'datatable'])->name('category.datatable');

        Route::get('/supplier', [SupplierController::class, 'index'])->name('supplier');
        Route::post('/supplier/datatable', [SupplierController::class, 'datatable'])->name('supplier.datatable');
    });

    Route::middleware('permission:ams.master-data.manage')->group(function () {
        Route::post('/regional', [RegionalController::class, 'store'])->name('regional.store');
        Route::get('/regional/edit/{id}', [RegionalController::class, 'show'])->name('regional.show');
        Route::put('/regional/edit/{id}', [RegionalController::class, 'update'])->name('regional.update');
        Route::delete('/regional/delete/{id}', [RegionalController::class, 'destroy'])->name('regional.delete');

        Route::post('/company', [CompanyController::class, 'store'])->name('company.store');
        Route::get('/company/edit/{id}', [CompanyController::class, 'show'])->name('company.show');
        Route::put('/company/edit/{id}', [CompanyController::class, 'update'])->name('company.update');
        Route::delete('/company/delete/{id}', [CompanyController::class, 'destroy'])->name('company.delete');

        Route::post('/division', [DivisionController::class, 'store'])->name('division.store');
        Route::get('/division/edit/{id}', [DivisionController::class, 'show'])->name('division.show');
        Route::put('/division/edit/{id}', [DivisionController::class, 'update'])->name('division.update');
        Route::delete('/division/delete/{id}', [DivisionController::class, 'destroy'])->name('division.delete');

        Route::post('/satuan-kerja', [SatuanKerjaController::class, 'store'])->name('satuan-kerja.store');
        Route::get('/satuan-kerja/edit/{id}', [SatuanKerjaController::class, 'show'])->name('satuan-kerja.show');
        Route::put('/satuan-kerja/edit/{id}', [SatuanKerjaController::class, 'update'])->name('satuan-kerja.update');
        Route::delete('/satuan-kerja/delete/{id}', [SatuanKerjaController::class, 'destroy'])->name('satuan-kerja.delete');

        Route::post('/master-posisi', [PegawaiMasterPosisiController::class, 'store'])->name('master-posisi.store');
        Route::get('/master-posisi/edit/{id}', [PegawaiMasterPosisiController::class, 'show'])->name('master-posisi.show');
        Route::put('/master-posisi/edit/{id}', [PegawaiMasterPosisiController::class, 'update'])->name('master-posisi.update');
        Route::delete('/master-posisi/delete/{id}', [PegawaiMasterPosisiController::class, 'destroy'])->name('master-posisi.delete');

        Route::post('/pegawai-hirarki', [PegawaiHirarkiController::class, 'store'])->name('pegawai-hirarki.store');
        Route::get('/pegawai-hirarki/edit/{id}', [PegawaiHirarkiController::class, 'show'])->name('pegawai-hirarki.show');
        Route::put('/pegawai-hirarki/edit/{id}', [PegawaiHirarkiController::class, 'update'])->name('pegawai-hirarki.update');
        Route::delete('/pegawai-hirarki/delete/{id}', [PegawaiHirarkiController::class, 'destroy'])->name('pegawai-hirarki.delete');

        Route::post('/master-hirarki', [MasterPegawaiHirarkiController::class, 'store'])->name('master-hirarki.store');
        Route::get('/master-hirarki/edit/{id}', [MasterPegawaiHirarkiController::class, 'show'])->name('master-hirarki.show');
        Route::put('/master-hirarki/edit/{id}', [MasterPegawaiHirarkiController::class, 'update'])->name('master-hirarki.update');
        Route::delete('/master-hirarki/delete/{id}', [MasterPegawaiHirarkiController::class, 'destroy'])->name('master-hirarki.delete');

        Route::post('/category', [CategoryController::class, 'store'])->name('category.store');
        Route::get('/category/edit/{id}', [CategoryController::class, 'show'])->name('category.show');
        Route::put('/category/edit/{id}', [CategoryController::class, 'update'])->name('category.update');
        Route::delete('/category/delete/{id}', [CategoryController::class, 'destroy'])->name('category.delete');

        Route::post('/supplier', [SupplierController::class, 'store'])->name('supplier.store');
        Route::get('/supplier/edit/{id}', [SupplierController::class, 'show'])->name('supplier.show');
        Route::put('/supplier/edit/{id}', [SupplierController::class, 'update'])->name('supplier.update');
        Route::delete('/supplier/delete/{id}', [SupplierController::class, 'destroy'])->name('supplier.delete');
    });

    // ASSET
    Route::middleware('permission:ams.assets.view')->group(function () {
        Route::get('/asset', [AssetController::class, 'index'])->name('asset');
        Route::post('/asset/datatable', [AssetController::class, 'datatable'])->name('asset.datatable');
    });

    // PENTING: Gunakan GET untuk mengambil data ke modal
    Route::get('/asset/edit/{id}', [AssetController::class, 'show'])->name('asset.show')->middleware('permission:ams.assets.edit');

    // Gunakan POST untuk simpan (Tambah/Update) agar tidak perlu pusing dengan spoofing PUT di AJAX
    Route::post('/asset/store', [AssetController::class, 'store'])->name('asset.store')->middleware('permission:ams.assets.create');

    Route::delete('/asset/delete/{id}', [AssetController::class, 'destroy'])->name('asset.delete')->middleware('permission:ams.assets.delete');
    Route::post('asset/import', [AssetController::class, 'import'])->name('asset.import')->middleware('permission:ams.assets.import');
    Route::get('asset/template', [AssetController::class, 'downloadTemplate'])->name('asset.template')->middleware('permission:ams.assets.import');

    // TRANSACTION (AMS)
    Route::middleware('permission:ams.transactions.view')->group(function () {
        Route::get('/transaction', [TransactionController::class, 'index'])->name('transaction.index');
        Route::post('/transaction/datatable', [TransactionController::class, 'datatable'])->name('transaction.datatable');
        Route::get('/transaction/detail/{id}', [TransactionController::class, 'show'])->name('transaction.detail');
    });

    Route::get('/transaction/create', [TransactionController::class, 'create'])->name('transaction.create')->middleware('permission:ams.transactions.create');
    Route::post('/transaction/store', [TransactionController::class, 'store'])->name('transaction.store')->middleware('permission:ams.transactions.create'); // Hanya satu saja
    Route::get('/transaction/pdf/{id}', [TransactionController::class, 'exportPDF'])->name('transaction.pdf')->middleware('permission:ams.transactions.export-pdf');
    Route::post('/transaction/update-status/{id}', [TransactionController::class, 'updateStatus'])->name('transaction.updateStatus')->middleware('permission:ams.transactions.approve');

    // Sesuaikan URL-nya dengan yang ada di JavaScript Anda
    Route::delete('/transaction/delete/{id}', [TransactionController::class, 'destroy'])->name('transaction.delete')->middleware('permission:ams.transactions.delete');
    Route::delete('/transaction/delete/{id}', [TransactionController::class, 'destroy'])->name('transaction.destroy')->middleware('permission:ams.transactions.delete');
    Route::get('/select/employee', [TransactionController::class, 'selectEmployee'])->name('select.employee')->middleware('permission:ams.transactions.view');
    Route::get('/select/asset/{id?}', [TransactionController::class, 'selectAsset'])->name('select.asset')->middleware('permission:ams.transactions.view');



    Route::middleware('permission:ams.monitoring.view')->group(function () {
        Route::get('/monitoring/asset', [MonitorController::class, 'asset'])->name('monitor.asset');
        Route::post('/monitoring/asset/datatable', [MonitorController::class, 'assetDatatable'])->name('monitor.asset.datatable');
        Route::get('/monitoring/asset/detail/{id}', [MonitorController::class, 'assetTransaction'])->name('monitor.asset.detail');

        Route::get('/monitoring/employee', [MonitorController::class, 'employee'])->name('monitor.employee');
        Route::post('/monitoring/employee/datatable', [MonitorController::class, 'employeeDatatable'])->name('monitor.employee.datatable');
        Route::get('/monitoring/employee/detail/{id}', [MonitorController::class, 'employeeTransaction'])->name('monitor.employee.detail');

        Route::get('/monitoring/company', [MonitorController::class, 'company'])->name('monitor.company');
        Route::post('/monitoring/company/datatable', [MonitorController::class, 'companyDatatable'])->name('monitor.company.datatable');
        Route::get('/monitoring/company/detail/{id}', [MonitorController::class, 'companyTransaction'])->name('monitor.company.detail');
    });

    // Select2
    Route::get('/select/category', [SelectController::class, 'category'])->name('select.category');
    Route::get('/select/supplier', [SelectController::class, 'supplier'])->name('select.supplier');
    Route::get('/select/employee', [SelectController::class, 'employee'])->name('select.employee');
    Route::get('/select/division', [SelectController::class, 'division'])->name('select.division');
    Route::get('/select/asset', [SelectController::class, 'asset'])->name('select.asset');
    Route::get('/select/asset/{id}', [SelectController::class, 'assetById'])->name('select.asset.id');

    // ASSIGNMENT
    Route::middleware('permission:ams.assignment.view')->group(function () {
        Route::get('/assignment', [AssetAssignmentController::class, 'index'])->name('assignment.index');
        Route::post('/assignment/datatable', [AssetAssignmentController::class, 'datatable'])->name('assignment.datatable');
    });

    Route::middleware('permission:ams.assignment.manage')->group(function () {
        Route::post('/assignment/store', [AssetAssignmentController::class, 'store'])->name('assignment.store');
        Route::post('/assignment/update/{id}', [AssetAssignmentController::class, 'update'])->name('assignment.update');
        Route::delete('/assignment/destroy/{id}', [AssetAssignmentController::class, 'destroy'])->name('assignment.destroy');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('permission:ams.dashboard');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/reset-password', [\App\Http\Controllers\SettingController::class, 'resetPasswordIndex'])->name('reset-password')->middleware('permission:ams.settings.reset-password');
        Route::post('/reset-password', [\App\Http\Controllers\SettingController::class, 'updatePassword'])->name('update-password')->middleware('permission:ams.settings.reset-password');
    });

    // Halaman Utama
    Route::get('/settings/approve', [ApprovedController::class, 'index'])->name('settings.approve')->middleware('permission:ams.settings.approve');

    // Proses Action (Approve/Reject) - Pastikan POST
    Route::post('/settings/approve/action/{id}', [ApprovedController::class, 'process'])->middleware('permission:ams.settings.approve');

    // Detail untuk Modal View
    Route::get('/transaction/detail/{id}', [ApprovedController::class, 'detail'])->middleware('permission:ams.settings.approve');

    Route::prefix('settings/role')->group(function () {
        Route::get('/', [UserRoleController::class, 'index'])->name('settings.role');
        Route::post('/store', [UserRoleController::class, 'store'])->name('settings.role.store');
        Route::get('/employee/{id}', [UserRoleController::class, 'getEmployeeDetail'])->name('settings.role.employee');
        Route::get('/edit/{id}', [UserRoleController::class, 'edit'])->name('settings.role.edit');
        Route::delete('/delete/{id}', [UserRoleController::class, 'destroy'])->name('settings.role.delete');
        // Route::post('settings/role/set-password', [UserRoleController::class, 'setPassword'])->name('settings.role.set-password');
        Route::post('/settings/role/set-password', [UserRoleController::class, 'setPassword'])->name('settings.role.set-password');
    });
});

Route::get('/login/microsoft', [MicrosoftAuthController::class, 'redirect'])
    ->name('login.microsoft');

Route::get('/login/microsoft/callback', [MicrosoftAuthController::class, 'callback'])
    ->name('login.microsoft.callback');

