<?php
use Illuminate\Support\Facades\Route;
// HelpDesk
use App\Http\Controllers\HelpDesk\DashboardController as HelpDeskDashboardController;
use App\Http\Controllers\HelpDesk\TicketCategoryController as HelpDeskTicketCategoryController;
use App\Http\Controllers\HelpDesk\TicketPriorityController as HelpDeskTicketPriorityController;
use App\Http\Controllers\HelpDesk\TicketController as HelpDeskTicketController;
use App\Http\Controllers\HelpDesk\TicketAttachmentController as HelpDeskTicketAttachmentController;
use App\Http\Controllers\HelpDesk\TicketCommentController as HelpDeskTicketCommentController;
use App\Http\Controllers\HelpDesk\ReportController as HelpDeskReportController;

Route::prefix('helpdesk')->name('helpdesk.')->group(function () {

        Route::get("/", [HelpDeskDashboardController::class, 'index'])->name('index');
        Route::get('dashboard/chart-data', [HelpDeskDashboardController::class, 'chartData'])->name('dashboard.chart-data');

        Route::get('tickets/{id}/timeline', [HelpDeskTicketController::class, 'timeline'])->name('tickets.timeline');

        Route::resource("tickets", HelpDeskTicketController::class);
        Route::post('tickets/datatable', [HelpDeskTicketController::class, 'datatable'])->name('tickets.datatable');
        Route::get('tickets/attachments/{id}/download', [HelpDeskTicketAttachmentController::class, 'download'])->name('tickets.attachments.download');

        Route::middleware(['auth', 'permission:ticket-categories.manage|ticket-priorities.manage'])->group(function () {
            Route::resource('ticket-categories', HelpDeskTicketCategoryController::class);
            Route::post('ticket-categories/datatable', [HelpDeskTicketCategoryController::class, 'datatable'])->name('ticket-categories.datatable');

            Route::resource('ticket-priorities', HelpDeskTicketPriorityController::class);
            Route::post('ticket-priorities/datatable', [HelpDeskTicketPriorityController::class, 'datatable'])->name('ticket-priorities.datatable');
        });

        Route::middleware(['auth', 'permission:tickets.assign|reports.view'])->group(function () {
            Route::get('technicians', [HelpDeskTicketController::class, 'getTeknisi'])->name('tickets.teknisi');

            Route::put('tickets/assign/{id}', [HelpDeskTicketController::class, 'assignTeknisi'])->name('tickets.assign');

            Route::post('reports/datatable', [HelpDeskReportController::class, 'datatable'])->name('reports.datatable');
            Route::get('reports/generate-pdf', [HelpDeskReportController::class, 'generatePdf'])->name('reports.generate-pdf');
            Route::get('reports/generate-excel', [HelpDeskReportController::class, 'generateExcel'])->name('reports.generate-excel');
            Route::resource("reports", HelpDeskReportController::class);
        });

        Route::middleware(['auth', 'permission:tickets.comment'])->group(function () {
            Route::get('tickets/{id}/comments', [HelpDeskTicketCommentController::class, 'index'])->name('tickets.comments.index');
            Route::post('tickets/{id}/comments', [HelpDeskTicketCommentController::class, 'store'])->name('tickets.comments.store');
        });

        Route::middleware(['auth', 'permission:tickets.resolve|tickets.approve'])->group(function () {
            Route::put('tickets/resolve/{id}', [HelpDeskTicketController::class, 'resolved'])->name('tickets.resolve');
            Route::put('tickets/approve/{id}', [HelpDeskTicketController::class, 'approve'])->name('tickets.approve');
        });

        Route::middleware(['auth', 'permission:tickets.confirm|tickets.reopen|tickets.edit|tickets.delete'])->group(function () {
            Route::put('tickets/confirm/{id}', [HelpDeskTicketController::class, 'confirm'])->name('tickets.confirm');
            Route::put('tickets/reopen/{id}', [HelpDeskTicketController::class, 'reopen'])->name('tickets.reopen');
            Route::put('tickets/{id}/update-content', [HelpDeskTicketController::class, 'updateContent'])->name('tickets.update-content');
        });

    });
