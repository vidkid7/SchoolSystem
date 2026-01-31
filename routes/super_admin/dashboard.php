<?php

use App\Http\Controllers\SuperAdmin\DashboardController;

Route::get('/super-admin/dashboard', [DashboardController::class, 'index'])->name('superAdmin.dashboard');