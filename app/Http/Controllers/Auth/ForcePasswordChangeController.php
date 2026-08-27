<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ForcePasswordChangeController extends Controller
{
    public function edit(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // If they already changed it, don't let them see this page
        if (!$user->is_first_login) {
            return redirect()->route('dashboard');
        }

        return view('auth.force-password-change');
    }

    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Update email and password, and turn off the first login flag
        $user->update([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_first_login' => false,
        ]);

        return redirect()->route('dashboard')->with('status', 'Credentials updated successfully. Welcome!');
    }
}
