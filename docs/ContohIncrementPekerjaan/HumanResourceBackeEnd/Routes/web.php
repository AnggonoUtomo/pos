<?php

declare(strict_types=1);

use App\Modules\HumanResource\HumanResource\Presentation\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])
    ->prefix('human-resource/employees')
    ->name('human-resource.employees.')
    ->group(function (): void {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
    });
