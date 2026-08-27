<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovalStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalStepController extends Controller
{
    private array $allowedRoleKeys = [
        'approver_division_chief',
        'approver_chief_personnel',
        'approver_ard_ms',
    ];

    public function index(Request $request)
    {
        $adminOfficeId = Auth::user()->employee->office_id ?? abort(403, 'No office assigned.');

        $steps = ApprovalStep::where('office_id', $adminOfficeId)
            ->orderBy('step_order')
            ->get();

        // If steps not yet created for this office, generate 3 defaults
        if ($steps->count() === 0) {
            $defaults = [
                ['step_order' => 1, 'role_key' => 'approver_division_chief', 'name' => 'Division Chief'],
                ['step_order' => 2, 'role_key' => 'approver_chief_personnel', 'name' => 'Chief Personnel'],
                ['step_order' => 3, 'role_key' => 'approver_ard_ms', 'name' => 'ARD-MS'],
            ];
            foreach ($defaults as $d) {
                ApprovalStep::create(['office_id' => $adminOfficeId] + $d);
            }
            $steps = ApprovalStep::where('office_id', $adminOfficeId)->orderBy('step_order')->get();
        }

        $allowedRoleKeys = $this->allowedRoleKeys;

        return view('admin.approval_steps.index', compact('steps', 'allowedRoleKeys'));
    }

    public function update(Request $request)
    {
        $adminOfficeId = Auth::user()->employee->office_id ?? abort(403);

        $data = $request->validate([
            'steps' => 'required|array',
            'steps.*.id' => 'required|integer|exists:approval_steps,id',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.role_key' => 'required|string|max:255',
        ]);

        foreach ($data['steps'] as $row) {
            if (!in_array($row['role_key'], $this->allowedRoleKeys, true)) {
                return back()->withErrors(['steps' => 'Invalid role key used in approval steps.']);
            }

            // Strictly secure to Admin's office
            $step = ApprovalStep::where('id', $row['id'])->where('office_id', $adminOfficeId)->first();
            if (!$step) abort(403, 'Unauthorized modification of approval steps.');

            $step->update([
                'name' => $row['name'],
                'role_key' => $row['role_key'],
            ]);
        }

        return back()->with('status', 'Approval steps updated successfully.');
    }
}
