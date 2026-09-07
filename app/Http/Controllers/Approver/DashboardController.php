<?php

namespace App\Http\Controllers\Approver;

use App\Http\Controllers\Controller;
use App\Models\ApprovalStep;
use App\Models\Division;
use App\Models\LeaveApplication;
use App\Models\LeaveApproval;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }
    public function index(Request $request)
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
        // Efficiency Metrics (Chief Personnel Only)
        // ---------------------------------------------------------
        $efficiencyMetrics = null;
        $divisions = collect();
        $selectedDivision = null;

        if ($user->hasRole('approver_chief_personnel') && $officeId) {
            // Get division filter from request
            $selectedDivision = $request->get('division_id');
            
            // Fetch all divisions in the office for filter dropdown
            $divisions = Division::where('office_id', $officeId)
                ->orderBy('name')
                ->get();

            // Only calculate efficiency metrics if:
            // 1. No division is selected (All Divisions), OR
            // 2. Administrative Division is selected
            $shouldShowMetrics = !$selectedDivision; // All divisions
            if ($selectedDivision) {
                $selectedDivisionModel = Division::find($selectedDivision);
                if ($selectedDivisionModel && strcasecmp($selectedDivisionModel->name, 'Administrative Division') === 0) {
                    $shouldShowMetrics = true;
                }
            }

            if ($shouldShowMetrics) {

            // Base query for leave applications with division filter
            $leaveQuery = LeaveApplication::where('office_id', $officeId);
            // Only apply division filter if Administrative Division is selected
            if ($selectedDivision) {
                $selectedDivisionModel = Division::find($selectedDivision);
                if ($selectedDivisionModel && strcasecmp($selectedDivisionModel->name, 'Administrative Division') === 0) {
                    $leaveQuery->whereHas('employee', function ($q) use ($selectedDivision) {
                        $q->where('division_id', $selectedDivision);
                    });
                }
            }

            // Calculate Average Approval Time (entire workflow)
            $approvedApplications = (clone $leaveQuery)
                ->where('status', 'approved')
                ->with(['approvals' => function ($q) {
                    $q->orderBy('acted_at', 'desc');
                }])
                ->get();

            $totalApprovalTime = 0;
            $approvalCount = 0;

            foreach ($approvedApplications as $application) {
                if ($application->date_filed && $application->approvals->isNotEmpty()) {
                    $lastApproval = $application->approvals->first();
                    if ($lastApproval->acted_at) {
                        $filedDate = Carbon::parse($application->date_filed);
                        $hours = $filedDate->diffInHours($lastApproval->acted_at);
                        $totalApprovalTime += $hours;
                        $approvalCount++;
                    }
                }
            }

            $avgApprovalTime = $approvalCount > 0 ? round($totalApprovalTime / $approvalCount, 2) : 0;

            // Calculate Average Step Response Time (per step)
            $stepResponseTimes = [];
            
            // Get approved applications with all their approvals ordered by step
            $approvedWithApprovals = (clone $leaveQuery)
                ->where('status', 'approved')
                ->with(['approvals' => function ($q) {
                    $q->orderBy('step_order');
                }])
                ->get();

            $stepTimes = []; // Store times for each step

            foreach ($approvedWithApprovals as $application) {
                $approvals = $application->approvals->sortBy('step_order');
                $previousTime = Carbon::parse($application->date_filed);

                foreach ($approvals as $approval) {
                    if ($approval->acted_at && $previousTime) {
                        $hours = $previousTime->diffInHours($approval->acted_at);
                        
                        if (!isset($stepTimes[$approval->step_order])) {
                            $stepTimes[$approval->step_order] = [];
                        }
                        $stepTimes[$approval->step_order][] = $hours;
                        
                        $previousTime = $approval->acted_at;
                    }
                }
            }

            // Calculate averages per step
            foreach ($stepTimes as $stepOrder => $times) {
                if (count($times) > 0) {
                    $avgHours = round(array_sum($times) / count($times), 2);
                    $stepHours = floor($avgHours);
                    $stepMins = round(($avgHours - $stepHours) * 60);
                    $stepResponseTimes[$stepOrder] = [
                        'avg_hours' => $avgHours,
                        'formatted' => $stepHours > 0 ? "{$stepHours}h {$stepMins}m" : "{$stepMins}m",
                        'count' => count($times)
                    ];
                }
            }

            // Calculate Approval Rate
            $totalApplications = (clone $leaveQuery)->count();
            $approvedApplicationsCount = (clone $leaveQuery)->where('status', 'approved')->count();
            $approvalRate = $totalApplications > 0 
                ? round(($approvedApplicationsCount / $totalApplications) * 100, 2) 
                : 0;

            // Format hours to hours and minutes
            $avgHours = floor($avgApprovalTime);
            $avgMins = round(($avgApprovalTime - $avgHours) * 60);
            $avgApprovalTimeFormatted = $avgHours > 0 ? "{$avgHours}h {$avgMins}m" : "{$avgMins}m";

            // Format step times to hours and minutes
            $formattedStepTimes = [];
            foreach ($stepResponseTimes as $step => $data) {
                $stepHours = floor($data['avg_hours']);
                $stepMins = round(($data['avg_hours'] - $stepHours) * 60);
                $formattedStepTimes[$step] = [
                    'avg_hours' => $data['avg_hours'],
                    'formatted' => $stepHours > 0 ? "{$stepHours}h {$stepMins}m" : "{$stepMins}m",
                    'count' => $data['count']
                ];
            }

            $efficiencyMetrics = [
                'avg_approval_time_hours' => $avgApprovalTime,
                'avg_approval_time_formatted' => $avgApprovalTimeFormatted,
                'step_response_times' => $formattedStepTimes,
                'approval_rate' => $approvalRate,
                'total_applications' => $totalApplications,
                'approved_count' => $approvedApplicationsCount,
            ];
            } // End of if ($selectedDivision)
        } // End of if ($user->hasRole('approver_chief_personnel') && $officeId)

        // ---------------------------------------------------------
        // 3. Calendar Data (Grouped by Date for Flatpickr)
        // ---------------------------------------------------------
        
        // Determine if user is personnel or chief personnel for status-based coloring
        $isPersonnelRole = $user->hasRole('approver_personnel') || $user->hasRole('approver_chief_personnel');
        
        // First, get approved leaves
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

        // Now get pending leaves that this approver needs to action
        $pendingLeavesQuery = LeaveApplication::with(['employee.user', 'leaveType'])
            ->where('status', 'pending');

        // Apply the same scoping logic for pending leaves
        if ($user->hasRole('approver_division_chief') && !$hasOfficeWideRole) {
            $divisionId = $user->employee?->division_id;
            $pendingLeavesQuery->whereHas('employee', fn($q) => $q->where('division_id', $divisionId));
        } else {
            $pendingLeavesQuery->where('office_id', $officeId);
        }

        // Only include pending leaves that are at this user's approval step
        if (!empty($myStepOrders)) {
            $pendingLeavesQuery->whereIn('current_step_order', $myStepOrders);
        }

        $pendingLeaves = $pendingLeavesQuery->get();

        // Get cancelled leaves (only for personnel roles)
        $cancelledLeaves = collect();
        if ($isPersonnelRole) {
            $cancelledLeavesQuery = LeaveApplication::with(['employee.user', 'leaveType'])
                ->where('status', 'cancelled');
                
            // Apply the same scoping logic for cancelled leaves
            if ($user->hasRole('approver_division_chief') && !$hasOfficeWideRole) {
                $divisionId = $user->employee?->division_id;
                $cancelledLeavesQuery->whereHas('employee', fn($q) => $q->where('division_id', $divisionId));
            } else {
                $cancelledLeavesQuery->where('office_id', $officeId);
            }
            
            $cancelledLeaves = $cancelledLeavesQuery->get();
        }

        $leavesByDate = [];
        
        // Leave type colors (for non-personnel roles)
        $leaveTypeColors = [
            'VL' => '#198754',  // Green
            'SL' => '#dc3545',  // Red
            'SPL' => '#0dcaf0', // Cyan
            'ML' => '#d63384',  // Pink
            'PL' => '#0d6efd',  // Blue
        ];
        
        // Status-based colors (for personnel roles)
        $statusColors = [
            'approved' => '#198754',  // Green
            'pending' => '#ffc107',   // Yellow
            'cancelled' => '#dc3545', // Red
        ];

        // Process approved leaves
        foreach ($approvedLeaves as $leave) {
            $empName = $leave->employee->user->first_name . ' ' . $leave->employee->user->last_name;
            $leaveCode = $leave->leaveType->code;
            
            // Use status-based color for personnel, leave type color for others
            $color = $isPersonnelRole ? $statusColors['approved'] : ($leaveTypeColors[$leaveCode] ?? '#6c757d');

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
                        'color' => $color,
                        'status' => 'approved'
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
                        'color' => $color,
                        'status' => 'approved'
                    ];
                    $start->addDay();
                }
            }
        }

        // Process pending leaves (add them to the same array with pending status)
        foreach ($pendingLeaves as $leave) {
            $empName = $leave->employee->user->first_name . ' ' . $leave->employee->user->last_name;
            $leaveCode = $leave->leaveType->code;
            
            // Use status-based color for personnel, leave type color for others
            $color = $isPersonnelRole ? $statusColors['pending'] : ($leaveTypeColors[$leaveCode] ?? '#6c757d');

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
                        'color' => $color,
                        'status' => 'pending'
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
                        'color' => $color,
                        'status' => 'pending'
                    ];
                    $start->addDay();
                }
            }
        }

        // Process cancelled leaves (only for personnel roles)
        foreach ($cancelledLeaves as $leave) {
            $empName = $leave->employee->user->first_name . ' ' . $leave->employee->user->last_name;
            $leaveCode = $leave->leaveType->code;
            $color = $statusColors['cancelled'];

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
                        'color' => $color,
                        'status' => 'cancelled'
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
                        'color' => $color,
                        'status' => 'cancelled'
                    ];
                    $start->addDay();
                }
            }
        }

        // Log dashboard access
        $this->auditLogService->logDashboardAccess($user, $request);

        return view('approver.dashboard', compact('stats', 'leavesByDate', 'isPersonnelRole', 'efficiencyMetrics', 'divisions', 'selectedDivision'));
    }
}
