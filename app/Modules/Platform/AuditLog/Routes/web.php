<?php

use App\Modules\Platform\AuditLog\Presentation\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->prefix('platform/audit-log')->name('platform.audit-log.')->group(function (): void {
    Route::get('/', [AuditLogController::class, 'index'])->name('index');
    Route::get('data', [AuditLogController::class, 'data'])->name('data');
    Route::get('{id}', [AuditLogController::class, 'show'])->whereNumber('id')->name('show');
});
