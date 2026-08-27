<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            // 1. Check if User has an Employee record
            // 2. Check if that Employee record is NOT 'active'
            if ($user->employee && $user->employee->status !== 'active') {

                // Force Logout
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Redirect back to login with error
                return redirect()->route('login')->withErrors([
                    'email' => 'Your account is currently ' . $user->employee->status . '.',
                ]);
            }
        }

        return $next($request);
    }
}
