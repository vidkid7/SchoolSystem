<?php

use App\Http\Controllers\SchoolHead\DashboardController;

Route::get('/head-school/dashboard', [DashboardController::class, 'index'])->name('headSchool.dashboard');