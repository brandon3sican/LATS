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
            $user->roles()->sync($roles);
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
            $employee->user->roles()->sync($roles);
        });

        return redirect()->route('super.users.index')->with('updated', 'User updated successfully.');
    }
}
