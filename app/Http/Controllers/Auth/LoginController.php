<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    // protected $redirectTo = RouteServiceProvider::HOME;
    public function redirectTo()
    {
        $role = Auth::user()->getRoleNames()[0];
        // Use direct URLs so redirect works even when route:cache is used (e.g. on Railway)
        switch ($role) {
            case 'Super Admin':
                return url('/admin/super-admin/dashboard');
            case 'District Admin':
                return url('/admin/district/dashboard');
            case 'Municipality Admin':
                return url('/admin/municipality/dashboard');
            case 'Head School':
                return url('/admin/head-school/dashboard');
            case 'School Admin':
                return url('/admin/school-admin/dashboard');
            case 'Teacher':
            case 'Accountant':
            case 'Librarian':
            case 'Principal':
            case 'Receptionist':
            case 'Student':
                return url('/admin');
            default:
                return url('/admin');
        }
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
}