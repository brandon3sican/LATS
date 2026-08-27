<?php

namespace App\Http\Controllers\Admin;

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
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $adminOfficeId = Auth::user()->employee->office_id ?? abort(403, 'No office assigned.');

        // STRICTLY SCOPED: Only fetch employees in the Admin's office
        $query = Employee::with(['user', 'office', 'division'])->where('office_id', $adminOfficeId);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        if ($request->has('division_id') && $request->division_id != '') {
            $query->where('division_id', $request->division_id);
        }

        $employees = $query->paginate(10)->withQueryString();
        $adminOffice = Office::find($adminOfficeId);

        $divisions = Division::where('office_id', $adminOfficeId)->get();

        return view('admin.employees.index', compact('employees', 'adminOffice', 'divisions'));
    }

    public function create()
    {
        $adminOfficeId = Auth::user()->employee->office_id ?? abort(403);

        $adminOffice = Office::find($adminOfficeId);
        $divisions = Division::where('office_id', $adminOfficeId)->get(); // Only divisions in their office
        $roles = Role::whereNotIn('key', ['super_admin', 'admin'])->get();

        return view('admin.employees.create', compact('adminOffice', 'divisions', 'roles'));
    }

    public function store(Request $request)
    {
        $adminOfficeId = Auth::user()->employee->office_id ?? abort(403);

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'division_id' => 'nullable|exists:divisions,id',
            'position_title' => 'required|string|max:255',
            'salary_grade' => 'nullable|integer|min:1|max:33',
            'sex' => 'nullable|string|in:M,F',
        ]);

        if ($request->division_id) {
             $div = Division::find($request->division_id);
             if (!$div || $div->office_id != $adminOfficeId) {
                  return back()->withErrors(['division_id' => 'Invalid division.']);
             }
        }

        DB::transaction(function () use ($request, $adminOfficeId) {
            $newUser = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            Employee::create([
                'user_id' => $newUser->id,
                'office_id' => $adminOfficeId, // FORCED to the Admin's office
                'division_id' => $request->division_id,
                'position_title' => $request->position_title,
                'salary_grade' => $request->salary_grade,
                'sex' => $request->sex,
                'status' => 'active',
            ]);

            $roles = $request->roles ?? [];
            $employeeRole = Role::where('key', 'employee')->first();
            if ($employeeRole && !in_array($employeeRole->id, $roles)) {
                $roles[] = $employeeRole->id;
            }
            $newUser->roles()->sync($roles);
        });

        return redirect()->route('admin.employees.index')->with('success', 'Employee created.');
    }

    public function edit($id)
    {
        $adminOfficeId = Auth::user()->employee->office_id ?? abort(403);
        $employee = Employee::with('user.roles')->findOrFail($id);

        // Prevent admin from editing employees outside their office
        if ($employee->office_id != $adminOfficeId) abort(403, 'Unauthorized.');

        $adminOffice = Office::find($adminOfficeId);
        $divisions = Division::where('office_id', $adminOfficeId)->get();
        $roles = Role::whereNotIn('key', ['super_admin', 'admin'])->get();

        return view('admin.employees.edit', compact('employee', 'adminOffice', 'divisions', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $adminOfficeId = Auth::user()->employee->office_id ?? abort(403);
        $employee = Employee::findOrFail($id);

        if ($employee->office_id != $adminOfficeId) abort(403, 'Unauthorized.');

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($employee->user->id)],
            'division_id' => 'nullable|exists:divisions,id',
            'status' => 'required|in:active,inactive,suspended',
            'salary_grade' => 'nullable|integer|min:1|max:33',
            'sex' => 'nullable|string|in:M,F',
        ]);

        if ($request->division_id) {
             $div = Division::find($request->division_id);
             if (!$div || $div->office_id != $adminOfficeId) {
                  return back()->withErrors(['division_id' => 'Invalid division.']);
             }
        }

        DB::transaction(function () use ($request, $employee, $adminOfficeId) {
            $userData = $request->only('first_name', 'middle_name', 'last_name', 'email');
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }
            $employee->user->update($userData);

            $employee->update([
                'office_id' => $adminOfficeId, // FORCED to the Admin's office
                'division_id' => $request->division_id,
                'position_title' => $request->position_title,
                'salary_grade' => $request->salary_grade,
                'sex' => $request->sex,
                'status' => $request->status,
            ]);

            $roles = $request->roles ?? [];
            $employeeRole = Role::where('key', 'employee')->first();
            if ($employeeRole && !in_array($employeeRole->id, $roles)) {
                $roles[] = $employeeRole->id;
            }
            $employee->user->roles()->sync($roles);
        });

        return redirect()->route('admin.employees.index')->with('success', 'Employee updated.');
    }
}
