<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\RedirectResponse;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $role = Auth::user()->getRoleNames()[0];
                // dd($role);

                // Use direct URLs so redirect works even when route:cache is used (e.g. on Railway)
                switch ($role) {
                    case 'Super Admin':
                        return new RedirectResponse(url('/admin/super-admin/dashboard'));
                    case 'District Admin':
                        return new RedirectResponse(url('/admin/district/dashboard'));
                    case 'Municipality Admin':
                        return new RedirectResponse(url('/admin/municipality/dashboard'));
                    case 'Head School':
                        return new RedirectResponse(url('/admin/head-school/dashboard'));
                    case 'School Admin':
                        return new RedirectResponse(url('/admin/school-admin/dashboard'));
                    case 'Teacher':
                    case 'Accountant':
                    case 'Librarian':
                    case 'Principal':
                    case 'Receptionist':
                    case 'Student':
                        return new RedirectResponse(url('/admin'));
                    default:
                        return redirect(RouteServiceProvider::HOME);
                }
            }
        }

        return $next($request);
    }
}