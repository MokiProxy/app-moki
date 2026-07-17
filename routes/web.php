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
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\AssetAssignmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ApprovedController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\WhatsappController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\Auth\MicrosoftAuthController;
use Illuminate\Support\Facades\Route;
use PHPUnit\TextUI\Help;

// HelpDesk
use App\Http\Controllers\HelpDesk\DashboardController as HelpDeskDashboardController;
use App\Http\Controllers\HelpDesk\TicketCategoryController as HelpDeskTicketCategoryController;
use App\Http\Controllers\HelpDesk\TicketPriorityController as HelpDeskTicketPriorityController;
use App\Http\Controllers\HelpDesk\TicketController as HelpDeskTicketController;
use App\Http\Controllers\HelpDesk\TicketAttachmentController as HelpDeskTicketAttachmentController;
use App\Http\Controllers\HelpDesk\TicketCommentController as HelpDeskTicketCommentController;
use App\Http\Controllers\HelpDesk\ReportController as HelpDeskReportController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- PUBLIC / TEST AREA (Bisa diakses tanpa login) ---
// Route::get('/portal-test', [PortalController::class, 'index'])->name('portal.test');
Route::get('/portal', [PortalController::class, 'index'])->name('portal.index');


// --- GUEST AREA (Hanya untuk yang belum login) ---
Route::group(['middleware' => ['guest']], function () {
    Route::get('/', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticated'])->name('login.process');
});

// --- AUTH AREA (Harus login) ---
Route::group(['middleware' => ['auth']], function () {

    // FITUR HELPDESK
    Route::prefix('helpdesk')->name('helpdesk.')->group(function () {
        Route::get("/", [HelpDeskDashboardController::class, 'index'])->name('index');
        Route::get('dashboard/chart-data', [HelpDeskDashboardController::class, 'chartData'])->name('dashboard.chart-data');
        Route::get('technicians', [HelpDeskTicketController::class, 'getTeknisi'])->name('tickets.teknisi');
        Route::get('tickets/{id}/timeline', [HelpDeskTicketController::class, 'timeline'])->name('tickets.timeline');

        Route::resource("tickets", HelpDeskTicketController::class);
        Route::post('tickets/datatable', [HelpDeskTicketController::class, 'datatable'])->name('tickets.datatable');
        Route::get('tickets/attachments/{id}/download', [HelpDeskTicketAttachmentController::class, 'download'])->name('tickets.attachments.download');
        Route::get('tickets/{id}/comments', [HelpDeskTicketCommentController::class, 'index'])->name('tickets.comments.index');
        Route::post('tickets/{id}/comments', [HelpDeskTicketCommentController::class, 'store'])->name('tickets.comments.store');

        // Admin Access
        Route::middleware(['can:admin-access'])->group(function () {
            Route::resource('ticket-categories', HelpDeskTicketCategoryController::class);
            Route::post('ticket-categories/datatable', [HelpDeskTicketCategoryController::class, 'datatable'])->name('ticket-categories.datatable');

            Route::resource('ticket-priorities', HelpDeskTicketPriorityController::class);
            Route::post('ticket-priorities/datatable', [HelpDeskTicketPriorityController::class, 'datatable'])->name('ticket-priorities.datatable');

            Route::put('tickets/assign/{id}', [HelpDeskTicketController::class, 'assignTeknisi'])->name('tickets.assign');

            Route::post('reports/datatable', [HelpDeskReportController::class, 'datatable'])->name('reports.datatable');
            Route::get('reports/generate-pdf', [HelpDeskReportController::class, 'generatePdf'])->name('reports.generate-pdf');
            Route::get('reports/generate-excel', [HelpDeskReportController::class, 'generateExcel'])->name('reports.generate-excel');
            Route::resource("reports", HelpDeskReportController::class);
        });

        // Staff Access
        Route::middleware(['can:staff-access'])->group(function () {
            Route::get("tickets/create", [HelpDeskTicketController::class, "create"])->name('tickets.create');
            Route::put('tickets/confirm/{id}', [HelpDeskTicketController::class, 'confirm'])->name('tickets.confirm');
            Route::put('tickets/reopen/{id}', [HelpDeskTicketController::class, 'reopen'])->name('tickets.reopen');
            Route::put('tickets/{id}/update-content', [HelpDeskTicketController::class, 'updateContent'])->name('tickets.update-content');
        });

        Route::middleware(['can:technician-access'])->group(function () {
            Route::put('tickets/approve/{id}', [HelpDeskTicketController::class, 'approve'])->name('tickets.approve');
            Route::put('tickets/resolve/{id}', [HelpDeskTicketController::class, 'resolved'])->name('tickets.resolve');
        });

    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // PORTAL UTAMA
    Route::get('/dashboard', [PortalController::class, 'index'])->name('dashboard');

    // DASHBOARD ANALYTIK AMS
    Route::get('/ams/analytics', [DashboardController::class, 'index'])->name('ams.analytics');

    // WHATSAPP SETTING
    Route::get('/setting-fonnte', [WhatsappController::class, 'index'])->name('setting-fonnte.index');
    Route::post('/setting-fonnte/update', [WhatsappController::class, 'update'])->name('setting-fonnte.update');

    // EMPLOYEE
    Route::prefix('employee')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('employee');
        Route::post('/datatable', [EmployeeController::class, 'datatable'])->name('employee.datatable');
        Route::post('/store', [EmployeeController::class, 'store'])->name('employee.store');
        Route::get('/show/{id}', [EmployeeController::class, 'show'])->name('employee.show');
        Route::put('/update/{id}', [EmployeeController::class, 'update'])->name('employee.update');
        Route::delete('/delete/{id}', [EmployeeController::class, 'destroy'])->name('employee.delete');
        Route::post('/import', [EmployeeController::class, 'import'])->name('employee.import');
        Route::get('/template', [EmployeeController::class, 'downloadTemplate'])->name('employee.template');
    });

    // MASTER DATA
    Route::get('/regional', [RegionalController::class, 'index'])->name('regional');
    Route::post('/regional', [RegionalController::class, 'store'])->name('regional.store');
    Route::get('/regional/edit/{id}', [RegionalController::class, 'show'])->name('regional.show');
    Route::put('/regional/edit/{id}', [RegionalController::class, 'update'])->name('regional.update');
    Route::delete('/regional/delete/{id}', [RegionalController::class, 'destroy'])->name('regional.delete');
    Route::post('/regional/datatable', [RegionalController::class, 'datatable'])->name('regional.datatable');

    Route::get('/company', [CompanyController::class, 'index'])->name('company');
    Route::post('/company', [CompanyController::class, 'store'])->name('company.store');
    Route::get('/company/edit/{id}', [CompanyController::class, 'show'])->name('company.show');
    Route::put('/company/edit/{id}', [CompanyController::class, 'update'])->name('company.update');
    Route::delete('/company/delete/{id}', [CompanyController::class, 'destroy'])->name('company.delete');
    Route::post('/company/datatable', [CompanyController::class, 'datatable'])->name('company.datatable');

    Route::get('/division', [DivisionController::class, 'index'])->name('division');
    Route::post('/division', [DivisionController::class, 'store'])->name('division.store');
    Route::get('/division/edit/{id}', [DivisionController::class, 'show'])->name('division.show');
    Route::put('/division/edit/{id}', [DivisionController::class, 'update'])->name('division.update');
    Route::delete('/division/delete/{id}', [DivisionController::class, 'destroy'])->name('division.delete');
    Route::post('/division/datatable', [DivisionController::class, 'datatable'])->name('division.datatable');


    Route::get('/category', [CategoryController::class, 'index'])->name('category');
    Route::post('/category', [CategoryController::class, 'store'])->name('category.store');
    Route::get('/category/edit/{id}', [CategoryController::class, 'show'])->name('category.show');
    Route::put('/category/edit/{id}', [CategoryController::class, 'update'])->name('category.update');
    Route::delete('/category/delete/{id}', [CategoryController::class, 'destroy'])->name('category.delete');
    Route::post('/category/datatable', [CategoryController::class, 'datatable'])->name('category.datatable');

    Route::get('/supplier', [SupplierController::class, 'index'])->name('supplier');
    Route::post('/supplier', [SupplierController::class, 'store'])->name('supplier.store');
    Route::get('/supplier/edit/{id}', [SupplierController::class, 'show'])->name('supplier.show');
    Route::put('/supplier/edit/{id}', [SupplierController::class, 'update'])->name('supplier.update');
    Route::delete('/supplier/delete/{id}', [SupplierController::class, 'destroy'])->name('supplier.delete');
    Route::post('/supplier/datatable', [SupplierController::class, 'datatable'])->name('supplier.datatable');



    // ASSET
    Route::get('/asset', [AssetController::class, 'index'])->name('asset');
    Route::post('/asset/datatable', [AssetController::class, 'datatable'])->name('asset.datatable');

    // PENTING: Gunakan GET untuk mengambil data ke modal
    Route::get('/asset/edit/{id}', [AssetController::class, 'show'])->name('asset.show');

    // Gunakan POST untuk simpan (Tambah/Update) agar tidak perlu pusing dengan spoofing PUT di AJAX
    Route::post('/asset/store', [AssetController::class, 'store'])->name('asset.store');

    Route::delete('/asset/delete/{id}', [AssetController::class, 'destroy'])->name('asset.delete');
    Route::post('asset/import', [AssetController::class, 'import'])->name('asset.import');
    Route::get('asset/template', [AssetController::class, 'downloadTemplate'])->name('asset.template');

    // TRANSACTION (AMS)
    Route::get('/transaction', [TransactionController::class, 'index'])->name('transaction.index');
    // Route::get('/transaction', [TransactionController::class, 'index'])->name('transaction');
    // Route::get('/transactions', [TransactionController::class, 'index'])->name('transaction');
    Route::get('/transaction/create', [TransactionController::class, 'create'])->name('transaction.create');
    Route::get('/transaction/detail/{id}', [TransactionController::class, 'show'])->name('transaction.detail');
    Route::get('/transaction/pdf/{id}', [TransactionController::class, 'exportPDF'])->name('transaction.pdf');
    Route::post('/transaction/store', [TransactionController::class, 'store'])->name('transaction.store'); // Hanya satu saja
    Route::post('/transaction/datatable', [TransactionController::class, 'datatable'])->name('transaction.datatable');
    Route::delete('/transaction/delete/{id}', [TransactionController::class, 'destroy'])->name('transaction.delete');
    Route::post('/transaction/update-status/{id}', [TransactionController::class, 'updateStatus'])->name('transaction.updateStatus');

    // Sesuaikan URL-nya dengan yang ada di JavaScript Anda
    Route::delete('/transaction/delete/{id}', [TransactionController::class, 'destroy'])->name('transaction.destroy');
    Route::get('/select/employee', [TransactionController::class, 'selectEmployee'])->name('select.employee');
    Route::get('/select/asset/{id?}', [TransactionController::class, 'selectAsset'])->name('select.asset');



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


Route::get('/assignment', [AssetAssignmentController::class, 'index'])->name('assignment.index');
Route::post('/assignment/datatable', [AssetAssignmentController::class, 'datatable'])->name('assignment.datatable');
Route::post('/assignment/store', [AssetAssignmentController::class, 'store'])->name('assignment.store');

// TAMBAHKAN BARIS INI
Route::post('/assignment/update/{id}', [AssetAssignmentController::class, 'update'])->name('assignment.update');

Route::delete('/assignment/destroy/{id}', [AssetAssignmentController::class, 'destroy'])->name('assignment.destroy');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Pastikan SettingController sudah dibuat
Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/approve', [SettingController::class, 'approveIndex'])->name('approve');
    Route::get('/role', [SettingController::class, 'roleIndex'])->name('role');
    Route::get('/reset-password', [SettingController::class, 'resetPasswordIndex'])->name('reset-password');
    Route::post('/reset-password', [SettingController::class, 'updatePassword'])->name('update-password');
});



// Halaman Utama
Route::get('/settings/approve', [ApprovedController::class, 'index'])->name('settings.approve');

// Proses Action (Approve/Reject) - Pastikan POST
Route::post('/settings/approve/action/{id}', [ApprovedController::class, 'process']);

// Detail untuk Modal View
Route::get('/transaction/detail/{id}', [ApprovedController::class, 'detail']);


Route::prefix('settings/role')->group(function () {
    // Nama route diubah menjadi 'settings.role' agar match dengan sidebar Anda
    Route::get('/', [UserRoleController::class, 'index'])->name('settings.role');
    Route::post('/store', [UserRoleController::class, 'store'])->name('settings.role.store');
    Route::get('/employee/{id}', [UserRoleController::class, 'getEmployeeDetail'])->name('settings.role.employee');
    Route::get('/edit/{id}', [UserRoleController::class, 'edit'])->name('settings.role.edit');
    Route::delete('/delete/{id}', [UserRoleController::class, 'destroy'])->name('settings.role.delete');
    // Route::post('settings/role/set-password', [UserRoleController::class, 'setPassword'])->name('settings.role.set-password');
    Route::post('/settings/role/set-password', [UserRoleController::class, 'setPassword'])->name('settings.role.set-password');
});

Route::get('/login/microsoft', [MicrosoftAuthController::class, 'redirect'])
    ->name('login.microsoft');

Route::get('/login/microsoft/callback', [MicrosoftAuthController::class, 'callback'])
    ->name('login.microsoft.callback');
