<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\Division;
use App\Models\LeaveType;
use App\Models\LeaveApproval;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $adminOfficeId = Auth::user()->employee->office_id ?? abort(403, 'No office assigned.');

        // 1. Stats scoped strictly to this Office
        $stats = [
            'employees' => Employee::where('office_id', $adminOfficeId)->count(),
            'pending' => LeaveApplication::where('status', 'pending')
                ->whereHas('employee', fn($q) => $q->where('office_id', $adminOfficeId))->count(),
            'approved_today' => LeaveApplication::where('status', 'approved')
                ->whereDate('updated_at', today())
                ->whereHas('employee', fn($q) => $q->where('office_id', $adminOfficeId))->count(),
            'male'   => Employee::where('office_id', $adminOfficeId)->where('sex', 'M')->count(),
            'female' => Employee::where('office_id', $adminOfficeId)->where('sex', 'F')->count(),
        ];

        // 2. Fetch Divisions ONLY for this Office
        $divisions = Division::where('office_id', $adminOfficeId)->get();
        $leaveTypes = LeaveType::all();

        // 3. Build Temporary Chart Data (to calculate totals)
        $tempLabels = $leaveTypes->pluck('name')->toArray();
        $tempAllCounts = array_fill(0, count($tempLabels), 0);
        $tempDivCounts = [];

        foreach ($divisions as $division) {
            $divData = [];
            foreach ($leaveTypes as $index => $type) {
                // Count approved leaves for this specific leave type and division
                $count = LeaveApplication::where('leave_type_id', $type->id)
                    ->where('status', 'approved')
                    ->whereHas('employee', fn($q) => $q->where('division_id', $division->id))
                    ->count();

                $divData[] = $count;
                $tempAllCounts[$index] += $count;
            }
            $tempDivCounts[$division->id] = $divData;
        }

        // 4. FILTER: Only keep leave types that have data (> 0)
        $chartLabels = [];
        $chartData = [
            'all' => []
        ];

        foreach ($divisions as $division) {
            $chartData[$division->id] = [];
        }

        foreach ($tempAllCounts as $index => $total) {
            // If the total approved across the office for this leave type is more than 0, include it
            if ($total > 0) {
                $chartLabels[] = $tempLabels[$index];
                $chartData['all'][] = $total;

                foreach ($divisions as $division) {
                    $chartData[$division->id][] = $tempDivCounts[$division->id][$index];
                }
            }
        }

        // 5. Calculate Efficiency Metrics for Admin Office (only if admin has division assigned)
        // Note: Super admin is exempt from division requirement but won't access admin dashboard
        $efficiencyMetrics = null;
        if (Auth::user()->employee && Auth::user()->employee->division_id) {
            $efficiencyMetrics = $this->calculateEfficiencyMetrics($adminOfficeId);
        }

        return view('admin.dashboard', compact('stats', 'divisions', 'chartLabels', 'chartData', 'efficiencyMetrics'));
    }

    /**
     * Calculate efficiency metrics for leave approval workflow
     */
    private function calculateEfficiencyMetrics(int $officeId): array
    {
        // Get all approved leave applications for this office
        $leaveQuery = LeaveApplication::where('office_id', $officeId)->where('status', 'approved');

        // Calculate average approval time
        $approvals = LeaveApproval::whereHas('leaveApplication', function($q) use ($officeId) {
            $q->where('office_id', $officeId)->where('status', 'approved');
        })->get();

        $totalApprovalTime = 0;
        $approvalCount = 0;

        foreach ($approvals as $approval) {
            if ($approval->leaveApplication && $approval->leaveApplication->date_filed) {
                $filedDate = Carbon::parse($approval->leaveApplication->date_filed);
                $approvedDate = $approval->acted_at ?? $approval->created_at;
                $hours = $filedDate->diffInHours($approvedDate);
                $totalApprovalTime += $hours;
                $approvalCount++;
            }
        }

        $avgApprovalTime = $approvalCount > 0 ? $totalApprovalTime / $approvalCount : 0;

        // Calculate step response times
        $stepResponseTimes = [];
        $approvalsByStep = $approvals->groupBy('step_order');

        foreach ($approvalsByStep as $stepOrder => $stepApprovals) {
            $times = [];
            foreach ($stepApprovals as $approval) {
                if ($approval->leaveApplication && $approval->leaveApplication->date_filed) {
                    $filedDate = Carbon::parse($approval->leaveApplication->date_filed);
                    $approvedDate = $approval->acted_at ?? $approval->created_at;
                    $hours = $filedDate->diffInHours($approvedDate);
                    $times[] = $hours;
                }
            }

            if (!empty($times)) {
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

        // Calculate approval rate
        $totalApplications = LeaveApplication::where('office_id', $officeId)->count();
        $approvedApplicationsCount = (clone $leaveQuery)->count();
        $approvalRate = $totalApplications > 0
            ? round(($approvedApplicationsCount / $totalApplications) * 100, 2)
            : 0;

        // Format overall approval time to hours and minutes
        $avgHours = floor($avgApprovalTime);
        $avgMins = round(($avgApprovalTime - $avgHours) * 60);
        $avgApprovalTimeFormatted = $avgHours > 0 ? "{$avgHours}h {$avgMins}m" : "{$avgMins}m";

        // Bottleneck Analysis: Identify slowest approval step
        $bottleneckAnalysis = null;
        if (!empty($stepResponseTimes)) {
            $slowestStep = null;
            $maxAvgHours = 0;

            foreach ($stepResponseTimes as $stepOrder => $data) {
                if ($data['avg_hours'] > $maxAvgHours) {
                    $maxAvgHours = $data['avg_hours'];
                    $slowestStep = $stepOrder;
                }
            }

            if ($slowestStep !== null) {
                $bottleneckAnalysis = [
                    'step_order' => $slowestStep,
                    'formatted' => $stepResponseTimes[$slowestStep]['formatted'],
                    'avg_hours' => $stepResponseTimes[$slowestStep]['avg_hours']
                ];
            }
        }

        return [
            'avg_approval_time' => $avgApprovalTime,
            'avg_approval_time_formatted' => $avgApprovalTimeFormatted,
            'approved_count' => $approvedApplicationsCount,
            'total_applications' => $totalApplications,
            'approval_rate' => $approvalRate,
            'step_response_times' => $stepResponseTimes,
            'bottleneck_analysis' => $bottleneckAnalysis
        ];
    }
}
