<?php

declare(strict_types=1);

use App\Modules\HumanResource\HumanResource\Presentation\Controllers\EmployeeApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified', 'throttle:system-api'])
    ->prefix('api/v1/human-resource/employees')
    ->name('api.v1.human-resource.employees.')
    ->group(function (): void {
        Route::get('/', [EmployeeApiController::class, 'index'])->name('index');
        Route::post('/', [EmployeeApiController::class, 'store'])
            ->middleware('api.idempotency')
            ->name('store');
        Route::patch('/{employee}', [EmployeeApiController::class, 'update'])
            ->whereUlid('employee')
            ->middleware('api.idempotency')
            ->name('update');
        Route::patch('/{employee}/activate', [EmployeeApiController::class, 'activate'])
            ->whereUlid('employee')
            ->middleware('api.idempotency')
            ->name('activate');
        Route::patch('/{employee}/deactivate', [EmployeeApiController::class, 'deactivate'])
            ->whereUlid('employee')
            ->middleware('api.idempotency')
            ->name('deactivate');
        Route::post('/{employee}/unit-assignments', [EmployeeApiController::class, 'storeUnitAssignment'])
            ->whereUlid('employee')
            ->middleware('api.idempotency')
            ->name('unit-assignments.store');
    });
