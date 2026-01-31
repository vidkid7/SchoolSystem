<?php

use App\Http\Controllers\LocalBodyAdmin\DashboardController;

Route::get('/local-body/dashboard', [DashboardController::class, 'index'])->name('localBody.dashboard');