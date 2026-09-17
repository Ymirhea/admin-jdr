<?php

use App\Enums\Permission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permission:'.Permission::AccessAdmin->value])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::view('/', 'admin.dashboard')->name('dashboard');
    });
