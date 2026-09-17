<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Division;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Notifications\NewUserRegistrationNotification;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with(['user.roles', 'office', 'division']);

        // Search Filter
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        // Office Filter
        if ($officeId = $request->get('office_id')) {
            $query->where('office_id', $officeId);
        }

        // Role Filter
        if ($roleId = $request->get('role_id')) {
            $query->whereHas('user.roles', function ($q) use ($roleId) {
                $q->where('roles.id', $roleId);
            });
        }

        $employees = $query->paginate(15)->withQueryString();
        $offices = Office::orderBy('name')->get();
        $divisions = Division::all();
        $roles = Role::all();

        return view('super.users.index', compact('employees', 'offices', 'divisions', 'roles'));
    }

    public function create()
    {
        $offices = Office::orderBy('name')->get();
        $divisions = Division::all();
        $roles = Role::all(); // Super Admin can assign ANY role

        return view('super.users.create', compact('offices', 'divisions', 'roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'office_id' => 'required|exists:offices,id',
            'division_id' => 'nullable|exists:divisions,id',
            'position_title' => 'required|string|max:255',
            'salary_grade' => 'nullable|integer|min:1|max:33',
            'sex' => 'nullable|string|in:M,F',
            'roles' => 'array'
        ]);

        DB::transaction(function () use ($request) {
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_first_login' => false, // Don't force password change on first login
            ]);

            Employee::create([
                'user_id' => $user->id,
                'office_id' => $request->office_id,
                'division_id' => $request->division_id,
                'position_title' => $request->position_title,
                'salary_grade' => $request->salary_grade,
                'sex' => $request->sex,
                'status' => 'active',
            ]);

            $roles = $request->roles ?? [];
            // Ensure all users have the employee role
            $employeeRole = Role::where('key', 'employee')->first();
            if ($employeeRole && !in_array($employeeRole->id, $roles)) {
                $roles[] = $employeeRole->id;
            }
            $user->roles()->sync($roles);

            // Notify all super admins about the new user creation (excluding the creator)
            $superAdmins = User::whereHas('roles', function($query) {
                $query->where('key', 'super_admin');
            })->where('id', '!=', auth()->id())->get();

            foreach ($superAdmins as $superAdmin) {
                $superAdmin->notify(new NewUserRegistrationNotification($user));
            }
        });

        return redirect()->route('super.users.index')->with('success', 'User created successfully.');
    }

    public function edit($id)
    {
        $employee = Employee::with('user.roles')->findOrFail($id);
        $offices = Office::orderBy('name')->get();
        $divisions = Division::all();
        $roles = Role::all();

        return view('super.users.edit', compact('employee', 'offices', 'divisions', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($employee->user->id)],
            'office_id' => 'required|exists:offices,id',
            'division_id' => 'nullable|exists:divisions,id',
            'position_title' => 'required|string|max:255',
            'salary_grade' => 'nullable|integer|min:1|max:33',
            'sex' => 'nullable|string|in:M,F',
            'status' => 'required|in:active,inactive,suspended',
            'roles' => 'array'
        ]);

        DB::transaction(function () use ($request, $employee) {
            $userData = $request->only('first_name', 'middle_name', 'last_name', 'email');

            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            $employee->user->update($userData);

            $employee->update([
                'office_id' => $request->office_id,
                'division_id' => $request->division_id,
                'position_title' => $request->position_title,
                'salary_grade' => $request->salary_grade,
                'sex' => $request->sex,
                'status' => $request->status,
            ]);

            $roles = $request->roles ?? [];
            // Ensure all users have the employee role
            $employeeRole = Role::where('key', 'employee')->first();
            if ($employeeRole && !in_array($employeeRole->id, $roles)) {
                $roles[] = $employeeRole->id;
            }
            $employee->user->roles()->sync($roles);
        });

        return redirect()->route('super.users.index')->with('updated', 'User updated successfully.');
    }

    public function show($id)
    {
        try {
            $employee = Employee::with(['user.roles', 'office', 'division'])->findOrFail($id);
            return view('super.users.show', compact('employee'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('super.users.index')->with('error', 'User not found. This user may have been deleted.');
        }
    }

    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);

        // Prevent deletion of the current user
        if (auth()->id() === $employee->user_id) {
            return redirect()->route('super.users.index')->with('error', 'You cannot delete your own account.');
        }

        DB::transaction(function () use ($employee) {
            // Delete user roles
            $employee->user->roles()->detach();
            // Delete employee record
            $employee->delete();
            // Delete user record
            $employee->user->delete();
        });

        return redirect()->route('super.users.index')->with('success', 'User deleted successfully.');
    }

    public function resetPassword(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        // Prevent resetting own password
        if (auth()->id() === $employee->user_id) {
            return redirect()->route('super.users.index')->with('error', 'You cannot reset your own password.');
        }

        DB::transaction(function () use ($employee) {
            $employee->user->update([
                'password' => Hash::make('password'),
                'is_first_login' => false, // Don't force password change on reset
            ]);
        });

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Password reset successfully.']);
        }

        return redirect()->route('super.users.index')->with('success', 'Password reset successfully.');
    }

    public function getModalData($id)
    {
        try {
            $employee = Employee::with(['user.roles', 'office', 'division'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'user' => [
                    'first_name' => $employee->user->first_name,
                    'middle_name' => $employee->user->middle_name,
                    'last_name' => $employee->user->last_name,
                    'name' => $employee->user->name,
                    'email' => $employee->user->email,
                ],
                'status' => $employee->status,
                'office' => $employee->office->name ?? null,
                'division' => $employee->division->name ?? null,
                'position_title' => $employee->position_title,
                'salary_grade' => $employee->salary_grade,
                'sex' => $employee->sex,
                'roles' => $employee->user->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'key' => $role->key,
                    ];
                })->toArray(),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found. This user may have been deleted.'
            ], 404);
        }
    }
}
