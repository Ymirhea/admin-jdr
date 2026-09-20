<?php

use App\Enums\Permission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permission:'.Permission::AccessAdmin->value])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::view('/', 'admin.dashboard')->name('dashboard');

        Route::middleware('permission:'.Permission::UsersManage->value)->group(function () {
            Route::livewire('users/create', 'pages::admin.users.form')->name('users.create');
            Route::livewire('users/{user}/edit', 'pages::admin.users.form')->name('users.edit');
        });

        Route::middleware('permission:'.Permission::UsersView->value)->group(function () {
            Route::livewire('users/{user}', 'pages::admin.users.show')->name('users.show');
            Route::livewire('users', 'pages::admin.users.index')->name('users.index');
        });
    });
