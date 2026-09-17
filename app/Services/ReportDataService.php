<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Division;
use App\Models\LeaveApplication;
use App\Models\LeaveApproval;
use App\Models\Office;
use Carbon\Carbon;

class ReportDataService
{
    /**
     * Get efficiency metrics data for PDF report generation
     *
     * @param int|null $divisionId
     * @param array $dateRange [Carbon $from, Carbon $to]
     * @return array
     */
    public function getEfficiencyMetricsData(?int $divisionId, array $dateRange): array
    {
        [$from, $to] = $dateRange;

        // Get division information
        $division = $divisionId ? Division::find($divisionId) : null;
        $divisionName = $division ? $division->name : 'All Divisions';
        $office = $division ? $division->office : null;
        $officeName = $office ? $office->name : 'All Offices';

        // Base query for leave applications with filters
        $leaveQuery = LeaveApplication::query()
            ->whereBetween('date_filed', [$from->toDateString(), $to->toDateString()]);

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
        
        $approvedWithApprovals = (clone $leaveQuery)
            ->where('status', 'approved')
            ->with(['approvals' => function ($q) {
                $q->orderBy('step_order');
            }])
            ->get();

        $stepTimes = [];

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

        $stepNames = [
            1 => 'Division Chief Review',
            2 => 'Personnel Review',
            3 => 'Chief Personnel Approval',
            4 => 'ARD Approval'
        ];

        foreach ($stepTimes as $stepOrder => $times) {
            if (count($times) > 0) {
                $avgHours = round(array_sum($times) / count($times), 2);
                $stepHours = floor($avgHours);
                $stepMins = round(($avgHours - $stepHours) * 60);
                $stepResponseTimes[$stepOrder] = [
                    'step_name' => $stepNames[$stepOrder] ?? "Step {$stepOrder}",
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

        // Format overall approval time
        $avgHours = floor($avgApprovalTime);
        $avgMins = round(($avgApprovalTime - $avgHours) * 60);
        $avgApprovalTimeFormatted = $avgHours > 0 ? "{$avgHours}h {$avgMins}m" : "{$avgMins}m";

        // Bottleneck Analysis
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
                    'step_name' => $stepResponseTimes[$slowestStep]['step_name'],
                    'avg_hours' => $stepResponseTimes[$slowestStep]['avg_hours'],
                    'formatted' => $stepResponseTimes[$slowestStep]['formatted'],
                    'count' => $stepResponseTimes[$slowestStep]['count'],
                ];
            }
        }

        return [
            'division_name' => $divisionName,
            'office_name' => $officeName,
            'avg_approval_time_hours' => $avgApprovalTime,
            'avg_approval_time_formatted' => $avgApprovalTimeFormatted,
            'step_response_times' => $stepResponseTimes,
            'approval_rate' => $approvalRate,
            'total_applications' => $totalApplications,
            'approved_count' => $approvedApplicationsCount,
            'bottleneck_analysis' => $bottleneckAnalysis,
        ];
    }

    /**
     * Get audit trail data for PDF report generation
     *
     * @param int|null $divisionId
     * @param array $dateRange [Carbon $from, Carbon $to]
     * @return array
     */
    public function getAuditTrailData(?int $divisionId, array $dateRange): array
    {
        [$from, $to] = $dateRange;

        // Get division information
        $division = $divisionId ? Division::find($divisionId) : null;
        $divisionName = $division ? $division->name : 'All Divisions';
        $office = $division ? $division->office : null;
        $officeName = $office ? $office->name : 'All Offices';

        // Get audit logs with filters
        $auditLogsQuery = AuditLog::query()
            ->whereBetween('created_at', [$from, $to])
            ->with(['user.employee', 'office', 'division'])
            ->orderBy('created_at', 'desc');

        if ($divisionId) {
            $auditLogsQuery->where('division_id', $divisionId);
        }

        $auditLogs = $auditLogsQuery->get();

        // Summary statistics
        $summary = [
            'total_logs' => $auditLogs->count(),
            'by_action' => $auditLogs->groupBy('action_type')->map->count(),
            'by_step' => $auditLogs->whereNotNull('step_order')->groupBy('step_order')->map->count(),
            'by_user' => $auditLogs->groupBy('user_id')->map->count(),
        ];

        // Per-step analysis
        $stepAnalysis = [];
        $stepNames = [
            1 => 'Division Chief Review',
            2 => 'Personnel Review',
            3 => 'Chief Personnel Approval',
            4 => 'ARD Approval'
        ];

        foreach ($stepNames as $stepOrder => $stepName) {
            $stepLogs = $auditLogs->where('step_order', $stepOrder);
            if ($stepLogs->isNotEmpty()) {
                $stepAnalysis[$stepOrder] = [
                    'step_name' => $stepName,
                    'total_actions' => $stepLogs->count(),
                    'by_action' => $stepLogs->groupBy('action_type')->map->count(),
                ];
            }
        }

        // Approver performance summary
        $approverPerformance = [];
        $userActions = $auditLogs->groupBy('user_id');
        
        foreach ($userActions as $userId => $logs) {
            $user = $logs->first()->user;
            if ($user && $user->employee) {
                $approverPerformance[] = [
                    'user_name' => $user->employee->full_name,
                    'total_actions' => $logs->count(),
                    'by_action' => $logs->groupBy('action_type')->map->count(),
                ];
            }
        }

        // Sort by total actions
        usort($approverPerformance, function ($a, $b) {
            return $b['total_actions'] - $a['total_actions'];
        });

        return [
            'division_name' => $divisionName,
            'office_name' => $officeName,
            'audit_logs' => $auditLogs,
            'summary' => $summary,
            'step_analysis' => $stepAnalysis,
            'approver_performance' => $approverPerformance,
        ];
    }

    /**
     * Get combined analysis data for PDF report generation
     *
     * @param int|null $divisionId
     * @param array $dateRange [Carbon $from, Carbon $to]
     * @return array
     */
    public function getCombinedAnalysisData(?int $divisionId, array $dateRange): array
    {
        // Get both efficiency metrics and audit trail data
        $efficiencyData = $this->getEfficiencyMetricsData($divisionId, $dateRange);
        $auditData = $this->getAuditTrailData($divisionId, $dateRange);

        // Combine and add additional analysis
        $combinedData = array_merge($efficiencyData, $auditData);

        // Add recommendations based on analysis
        $recommendations = $this->generateRecommendations($efficiencyData, $auditData);

        $combinedData['recommendations'] = $recommendations;

        return $combinedData;
    }

    /**
     * Generate recommendations based on efficiency and audit data
     *
     * @param array $efficiencyData
     * @param array $auditData
     * @return array
     */
    private function generateRecommendations(array $efficiencyData, array $auditData): array
    {
        $recommendations = [];

        // Check for bottlenecks
        if ($efficiencyData['bottleneck_analysis']) {
            $bottleneck = $efficiencyData['bottleneck_analysis'];
            $recommendations[] = [
                'type' => 'bottleneck',
                'priority' => 'high',
                'title' => 'Address Workflow Bottleneck',
                'description' => "The {$bottleneck['step_name']} step has the longest average response time ({$bottleneck['formatted']}). Consider reviewing approval processes, adding approvers, or implementing automated notifications.",
            ];
        }

        // Check approval rate
        if ($efficiencyData['approval_rate'] < 70) {
            $recommendations[] = [
                'type' => 'approval_rate',
                'priority' => 'medium',
                'title' => 'Improve Approval Rate',
                'description' => "The current approval rate is {$efficiencyData['approval_rate']}%. Review rejection reasons and provide guidance to applicants to improve success rates.",
            ];
        }

        // Check overall approval time
        if ($efficiencyData['avg_approval_time_hours'] > 72) {
            $recommendations[] = [
                'type' => 'approval_time',
                'priority' => 'medium',
                'title' => 'Reduce Approval Time',
                'description' => "The average approval time is {$efficiencyData['avg_approval_time_formatted']}. Consider implementing SLAs or automated reminders to approvers.",
            ];
        }

        // Check for inactive approvers
        if (!empty($auditData['approver_performance'])) {
            $lowActivityApprovers = array_filter($auditData['approver_performance'], function ($approver) {
                return $approver['total_actions'] < 5;
            });

            if (!empty($lowActivityApprovers)) {
                $recommendations[] = [
                    'type' => 'approver_activity',
                    'priority' => 'low',
                    'title' => 'Monitor Approver Activity',
                    'description' => 'Some approvers have low activity levels. Ensure all approvers are actively engaged in the approval process.',
                ];
            }
        }

        // General positive feedback
        if ($efficiencyData['approval_rate'] >= 90 && $efficiencyData['avg_approval_time_hours'] <= 48) {
            $recommendations[] = [
                'type' => 'positive',
                'priority' => 'info',
                'title' => 'Excellent Performance',
                'description' => 'The approval workflow is performing well with high approval rates and quick turnaround times. Continue current practices.',
            ];
        }

        return $recommendations;
    }
}