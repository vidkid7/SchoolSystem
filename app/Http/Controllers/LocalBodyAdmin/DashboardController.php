<?php

namespace App\Http\Controllers\LocalBodyAdmin;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $page_title = Auth::user()->getRoleNames()[0] . ' ' . 'Dashboard';
        return view('backend.local_body_admin.dashboard', compact('page_title'));
    }
}
