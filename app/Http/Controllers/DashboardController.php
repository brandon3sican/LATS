<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('roles');

        $hasRole = function (string $key) use ($user) {
            if (method_exists($user, 'hasRole')) return $user->hasRole($key);
            return $user->roles->contains('key', $key);
        };

        if ($hasRole('super_admin')) {
            return redirect()->route('super.offices.index');
        }

        if ($hasRole('office_admin')) {
            return redirect()->route('admin.approvalSteps.index');
        }

        if (
            $hasRole('approver_division_chief') ||
            $hasRole('approver_personnel') ||
            $hasRole('approver_chief_personnel') ||
            $hasRole('approver_ard_ms')
        ) {
            return redirect()->route('approver.inbox');
        }

        if ($hasRole('employee')) {
            return redirect()->route('employee.dashboard');
        }

        return abort(403, 'No role assigned.');
    }
}
