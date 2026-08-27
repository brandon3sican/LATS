<?php

namespace App\Http\Controllers\Approver;

use App\Http\Controllers\Controller;
use App\Models\ApprovalStep;
use App\Models\LeaveApplication;
use App\Models\LeaveApproval;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Now the editor knows this is a Model, so loadMissing works
        $user->loadMissing(['roles', 'employee']);

        $officeId = $user->employee?->office_id;

        // Ensure roleKeys() exists in your User model, otherwise use: $user->roles->pluck('key')->toArray()
        $roleKeys = method_exists($user, 'roleKeys') ? $user->roleKeys() : $user->roles->pluck('key')->toArray();

        $pendingCount = 0;

        if ($officeId) {
            // Find which steps belong to this user
            $myStepOrders = ApprovalStep::where('office_id', $officeId)
                ->whereIn('role_key', $roleKeys)
                ->pluck('step_order')
                ->all();

            if (!empty($myStepOrders)) {
                $divisionId = $user->employee?->division_id;

                $query = LeaveApplication::query()
                    ->where('office_id', $officeId)
                    ->where('status', 'pending')
                    ->where(function ($stepQ) use ($myStepOrders, $user, $divisionId) {
                        foreach ($myStepOrders as $step) {
                            if ($step == 1 && $user->hasRole('approver_division_chief')) {
                                $stepQ->orWhere(function ($subQ) use ($step, $divisionId) {
                                    $subQ->where('current_step_order', $step)
                                         ->whereHas('employee', fn($eq) => $eq->where('division_id', $divisionId));
                                });
                            } else {
                                $stepQ->orWhere('current_step_order', $step);
                            }
                        }
                    });

                $pendingCount = $query->count();
            }
        }
        // ---------------------------------------------------------
        // 2. Calculate PROCESSED (Count your specific actions)
        // ---------------------------------------------------------
        $processedCount = LeaveApproval::where('approver_user_id', $user->id)
            ->whereIn('action', ['approved', 'disapproved', 'returned', 'Approved Cancellation', 'Rejected Cancellation'])
            ->count();

        // ---------------------------------------------------------
        // 2.5 Calculate CANCELLATION REQUESTS (Personnel Only)
        // ---------------------------------------------------------
        $cancellationCount = 0;
        if ($officeId && $user->hasRole('approver_personnel')) {
            $cancellationCount = LeaveApplication::where('office_id', $officeId)
                ->where('cancellation_status', 'pending')
                ->count();
        }

        // ---------------------------------------------------------
        // Demographic stats scoped to Approver's Office or Division
        // ---------------------------------------------------------
        $workforceQuery = \App\Models\Employee::where('office_id', $officeId);
        $scopeName = 'Office Workforce';

        // Check if user has an office-wide approver role
        $hasOfficeWideRole = $user->hasRole('approver_personnel') ||
                             $user->hasRole('approver_chief_personnel') ||
                             $user->hasRole('approver_chief_admin') ||
                             $user->hasRole('approver_ard_ms');

        // If ONLY a Division Chief, restrict to their specific division
        if ($user->hasRole('approver_division_chief') && !$hasOfficeWideRole) {
            $divisionId = $user->employee?->division_id;
            $workforceQuery->where('division_id', $divisionId);
            $scopeName = 'Division Workforce';
        }

        $stats = [
            'pending'       => $pendingCount,
            'processed'     => $processedCount,
            'cancellations' => $cancellationCount,
            'workforce'     => (clone $workforceQuery)->count(),
            'male'          => (clone $workforceQuery)->where('sex', 'M')->count(),
            'female'        => (clone $workforceQuery)->where('sex', 'F')->count(),
            'scope_name'    => $scopeName,
        ];

        // ---------------------------------------------------------
        // 3. Calendar Data (Grouped by Date for Flatpickr)
        // ---------------------------------------------------------
        $leavesQuery = LeaveApplication::with(['employee.user', 'leaveType'])
            ->where('status', 'approved');

        // Restrict calendar to Own Division if ONLY Division Chief
        if ($user->hasRole('approver_division_chief') && !$hasOfficeWideRole) {
            $divisionId = $user->employee?->division_id;
            $leavesQuery->whereHas('employee', fn($q) => $q->where('division_id', $divisionId));
        } else {
            // ARD or HR sees the whole office
            $leavesQuery->where('office_id', $officeId);
        }

        $approvedLeaves = $leavesQuery->get();

        $leavesByDate = [];
        $colors = [
            'VL' => '#198754',  // Green
            'SL' => '#dc3545',  // Red
            'SPL' => '#0dcaf0', // Cyan
            'ML' => '#d63384',  // Pink
            'PL' => '#0d6efd',  // Blue
        ];

        foreach ($approvedLeaves as $leave) {
            $empName = $leave->employee->user->first_name . ' ' . $leave->employee->user->last_name;
            $leaveCode = $leave->leaveType->code;
            $color = $colors[$leaveCode] ?? '#6c757d'; // Default gray if not mapped

            $details = $leave->details_json ?? [];
            if (is_string($details)) {
                $details = json_decode($details, true) ?? [];
            }

            // If using the exact specific dates (Flatpickr style array)
            if (!empty($details['selected_dates']) && is_array($details['selected_dates'])) {
                foreach ($details['selected_dates'] as $dateStr) {
                    $leavesByDate[$dateStr][] = [
                        'name' => $empName,
                        'leave_type' => $leave->leaveType->name,
                        'color' => $color
                    ];
                }
            } else {
                // Fallback for legacy continuous start/end dates
                $start = Carbon::parse($leave->start_date);
                $end = Carbon::parse($leave->end_date);
                while ($start->lte($end)) {
                    $dStr = $start->toDateString();
                    $leavesByDate[$dStr][] = [
                        'name' => $empName,
                        'leave_type' => $leave->leaveType->name,
                        'color' => $color
                    ];
                    $start->addDay();
                }
            }
        }

        return view('approver.dashboard', compact('stats', 'leavesByDate'));
    }
}
