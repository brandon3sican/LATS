<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Office;
use App\Models\Division;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use App\Models\Employee;
use App\Models\Role;
use App\Notifications\NewUserRegistrationNotification;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $offices = Office::orderBy('name')->get();
        $divisions = Division::all();
        return view('auth.register', compact('offices', 'divisions'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'office_id' => ['required', 'exists:offices,id'],
            'division_id' => ['nullable', 'exists:divisions,id'],
        ]);

        $user = User::create([
            'last_name' => $request->last_name,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_first_login' => false, // Don't force password change on first login
        ]);

        // Assign employee role to all new users
        $employeeRole = Role::where('key', 'employee')->first();
        if ($employeeRole) {
            $user->roles()->attach($employeeRole->id);
        }

        Employee::create([
            'user_id' => $user->id,
            'office_id' => $request->office_id,
            'division_id' => $request->division_id,
            'position_title' => 'Pending Assignment', // Default text
            'salary_grade' => null,
            'status' => 'active',
        ]);

        event(new Registered($user));

        // Notify all super admins about the new user registration
        $superAdmins = User::whereHas('roles', function($query) {
            $query->where('key', 'super_admin');
        })->get();

        foreach ($superAdmins as $superAdmin) {
            $superAdmin->notify(new NewUserRegistrationNotification($user));
        }

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
