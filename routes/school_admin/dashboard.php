<?php

use App\Http\Controllers\SchoolAdmin\DashboardController;

Route::get('/school-admin/dashboard', [DashboardController::class, 'index'])->name('schoolAdmin.dashboard');