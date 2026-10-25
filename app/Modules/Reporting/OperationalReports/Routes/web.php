<?php

use App\Modules\Reporting\OperationalReports\Presentation\Controllers\DashboardController;
use App\Modules\Reporting\OperationalReports\Presentation\Controllers\OperationalReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->get('/dashboard', DashboardController::class)->name('dashboard');

Route::middleware(['web', 'auth', 'verified'])->prefix('reports/operations')->name('reports.operations.')->group(function () {
    Route::get('/', [OperationalReportController::class, 'index'])->name('index');
    Route::get('data', [OperationalReportController::class, 'data'])->name('data');
});
