<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\LeaveApplication;
use App\Models\ApprovalStep;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function show(Request $request, int $id)
    {
        $leave = LeaveApplication::with([
            'leaveType',
            'office',
            'attachments',
            'approvals.approver',
            'employee.user',
            'employee.division',
        ])->findOrFail($id);

        $steps = ApprovalStep::where('office_id', $leave->office_id)
            ->orderBy('step_order')
            ->get();

        $approvalsByStep = $leave->approvals->keyBy('step_order');

        $timeline = $steps->map(function ($step) use ($leave, $approvalsByStep) {
            $ap = $approvalsByStep->get($step->step_order);

            if ($ap) {
                return [
                    'step_order' => $step->step_order,
                    'role_key'   => $step->role_key,
                    'title'      => $step->label ?? $step->role_key,
                    'state'      => $ap->action,
                    'remarks'    => $ap->remarks,
                    'actor'      => $ap->approver->name ?? null,
                    'acted_at'   => $ap->acted_at ?? $ap->created_at,
                ];
            }

            $isCurrent = ((int)$leave->current_step_order === (int)$step->step_order);

            return [
                'step_order' => $step->step_order,
                'role_key'   => $step->role_key,
                'title'      => $step->label ?? $step->role_key,
                'state'      => $isCurrent ? 'current' : 'upcoming',
                'remarks'    => null,
                'actor'      => null,
                'acted_at'   => null,
            ];
        });

        return view('super.leaves.show', compact('leave', 'timeline'));
    }
}
