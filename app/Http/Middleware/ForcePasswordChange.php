<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // If it's their first login, restrict access to everything EXCEPT the change-password routes and logout
            if ($user->is_first_login) {
                // Allow them to visit the change password page, submit the form, and logout
                if (!$request->routeIs('force-password.edit') && !$request->routeIs('force-password.update') && !$request->routeIs('logout')) {
                    return redirect()->route('force-password.edit');
                }
            }
        }

        return $next($request);
    }
}
