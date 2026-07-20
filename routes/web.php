<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/import', [ImportController::class, 'form'])->name('import.form');
Route::post('/import', [ImportController::class, 'store'])->name('import.store');
