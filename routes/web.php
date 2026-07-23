<?php

use App\Http\Controllers\BalanceReturController;
use App\Http\Controllers\BalanceReturImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/import', [ImportController::class, 'form'])->name('import.form');
Route::post('/import', [ImportController::class, 'store'])->name('import.store');

Route::get('/retur', [BalanceReturController::class, 'index'])->name('retur.dashboard');
Route::get('/retur/export', [BalanceReturController::class, 'export'])->name('retur.export');

Route::get('/retur/import', [BalanceReturImportController::class, 'form'])->name('retur.import.form');
Route::post('/retur/import', [BalanceReturImportController::class, 'store'])->name('retur.import.store');
