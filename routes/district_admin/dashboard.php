<?php

use App\Http\Controllers\DistrictAdmin\DashboardController;

Route::get('/district/dashboard', [DashboardController::class, 'index'])->name('district.dashboard');