<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveApproval;
use App\Models\Office;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Number of months (including the current one) shown in the trend chart.
     */
    private const TREND_MONTHS = 12;

    /**
     * Statuses tracked on the dashboard, in display order.
     *
     * @var list<string>
     */
    private const STATUSES = ['pending', 'approved', 'returned', 'disapproved', 'cancelled'];

    public function index(Request $request)
    {
        $divisionId = $request->get('division_id');
        
        $userQuery = User::query();
        $adminQuery = User::whereHas('roles', fn ($q) => $q->whereIn('key', ['office_admin', 'admin']));
        
        if ($divisionId) {
            $userQuery->whereHas('employee', function ($q) use ($divisionId) {
                $q->where('division_id', $divisionId);
            });
            $adminQuery->whereHas('employee', function ($q) use ($divisionId) {
                $q->where('division_id', $divisionId);
            });
        }
        
        // Only calculate efficiency metrics if:
        // 1. No division is selected (All Divisions), OR
        // 2. Administrative Division is selected
        $efficiencyMetrics = null;
        $shouldShowMetrics = !$divisionId; // All divisions
        $queryDivisionId = null;
        
        if ($divisionId) {
            $selectedDivision = Division::find($divisionId);
            if ($selectedDivision && strcasecmp($selectedDivision->name, 'Administrative Division') === 0) {
                $shouldShowMetrics = true;
                $queryDivisionId = $divisionId;
            }
        }
        
        if ($shouldShowMetrics) {
            $efficiencyMetrics = $this->calculateEfficiencyMetrics($queryDivisionId);
        }

        return view('super.dashboard', [
            'stats' => [
                'offices' => Office::count(),
                'users' => $userQuery->count(),
                'admins' => $adminQuery->count(),
                'leaves' => $this->leaveTotals($divisionId),
                'efficiency' => $efficiencyMetrics,
            ],
            'trend' => $this->monthlyTrend($divisionId),
            'divisions' => Division::orderBy('name')->get(),
            'selectedDivision' => $divisionId,
        ]);
    }

    /**
     * Overall count per status plus the grand total, in a single aggregate query.
     *
     * @param int|null $divisionId
     * @return array<string, int>
     */
    private function leaveTotals(?int $divisionId = null): array
    {
        $query = LeaveApplication::query()->selectRaw('COUNT(*) AS total');

        if ($divisionId) {
            $query->whereHas('employee', function ($q) use ($divisionId) {
                $q->where('division_id', $divisionId);
            });
        }

        foreach (self::STATUSES as $status) {
            $query->selectRaw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS {$status}", [$status]);
        }

        $row = $query->first();

        $totals = ['total' => (int) $row->total];

        foreach (self::STATUSES as $status) {
            $totals[$status] = (int) $row->{$status};
        }

        return $totals;
    }

    /**
     * Filed / approved / disapproved counts per month for the last TREND_MONTHS months.
     *
     * Months with no activity are zero-filled so the chart has an unbroken axis.
     *
     * @param int|null $divisionId
     * @return array{labels: list<string>, filed: list<int>, approved: list<int>, disapproved: list<int>}
     */
    private function monthlyTrend(?int $divisionId = null): array
    {
        $start = CarbonImmutable::now()->startOfMonth()->subMonths(self::TREND_MONTHS - 1);

        $period = match (LeaveApplication::query()->getConnection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', date_filed)",
            'pgsql' => "to_char(date_filed, 'YYYY-MM')",
            'sqlsrv' => "FORMAT(date_filed, 'yyyy-MM')",
            default => "DATE_FORMAT(date_filed, '%Y-%m')",
        };

        $query = LeaveApplication::query()
            ->where('date_filed', '>=', $start->toDateString());

        if ($divisionId) {
            $query->whereHas('employee', function ($q) use ($divisionId) {
                $q->where('division_id', $divisionId);
            });
        }

        $rows = $query
            ->groupBy(DB::raw($period))
            ->orderBy(DB::raw($period))
            ->get([
                DB::raw("{$period} AS period"),
                DB::raw('COUNT(*) AS filed'),
                DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved"),
                DB::raw("SUM(CASE WHEN status = 'disapproved' THEN 1 ELSE 0 END) AS disapproved"),
            ])
            ->keyBy('period');

        $trend = ['labels' => [], 'filed' => [], 'approved' => [], 'disapproved' => []];

        for ($i = 0; $i < self::TREND_MONTHS; $i++) {
            $month = $start->addMonths($i);
            $row = $rows->get($month->format('Y-m'));

            $trend['labels'][] = $month->format('M Y');
            $trend['filed'][] = (int) ($row->filed ?? 0);
            $trend['approved'][] = (int) ($row->approved ?? 0);
            $trend['disapproved'][] = (int) ($row->disapproved ?? 0);
        }

        return $trend;
    }

    /**
     * Calculate efficiency metrics for leave approval workflow.
     *
     * @param int|null $divisionId
     * @return array<string, mixed>
     */
    private function calculateEfficiencyMetrics(?int $divisionId = null): array
    {
        // Base query for leave applications with optional division filter
        $leaveQuery = LeaveApplication::query();
        if ($divisionId) {
            $leaveQuery->whereHas('employee', function ($q) use ($divisionId) {
                $q->where('division_id', $divisionId);
            });
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
                    'avg_hours' => $stepResponseTimes[$slowestStep]['avg_hours'],
                    'formatted' => $stepResponseTimes[$slowestStep]['formatted'],
                    'count' => $stepResponseTimes[$slowestStep]['count'],
                ];
            }
        }

        return [
            'avg_approval_time_hours' => $avgApprovalTime,
            'avg_approval_time_formatted' => $avgApprovalTimeFormatted,
            'step_response_times' => $stepResponseTimes,
            'approval_rate' => $approvalRate,
            'total_applications' => $totalApplications,
            'approved_count' => $approvedApplicationsCount,
            'bottleneck_analysis' => $bottleneckAnalysis,
        ];
    }
}
