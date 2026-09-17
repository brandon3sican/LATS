<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Division;
use App\Models\Office;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    // Role checking is handled in routes/web.php with middleware

    public function index(Request $request)
    {
        // Only fetch leave-related audit logs
        $query = AuditLog::with(['user', 'office', 'division', 'leaveApplication'])
            ->whereIn('action_type', ['creation', 'approval', 'cancellation', 'cancellation_request', 'cancellation_action', 'view']);

        // Apply filters
        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        if ($request->filled('action_type')) {
            $query->byActionType($request->action_type);
        }

        if ($request->filled('action')) {
            $query->byAction($request->action);
        }

        if ($request->filled('office_id')) {
            $query->byOffice($request->office_id);
        }

        if ($request->filled('division_id')) {
            $query->byDivision($request->division_id);
        }

        if ($request->filled('step_order')) {
            $query->byStep($request->step_order);
        }

        // Handle date filtering
        if ($request->filled('date_filter_type')) {
            $filterType = $request->date_filter_type;

            switch ($filterType) {
                case 'specific':
                    if ($request->filled('date_from') && $request->filled('date_to')) {
                        $query->byDateRange($request->date_from, $request->date_to);
                    } elseif ($request->filled('date_from')) {
                        $query->whereDate('created_at', '>=', $request->date_from);
                    } elseif ($request->filled('date_to')) {
                        $query->whereDate('created_at', '<=', $request->date_to);
                    }
                    break;

                case 'this_week':
                    $query->whereBetween('created_at', [
                        Carbon::now()->startOfWeek(),
                        Carbon::now()->endOfWeek()
                    ]);
                    break;

                case 'this_month':
                    $query->whereBetween('created_at', [
                        Carbon::now()->startOfMonth(),
                        Carbon::now()->endOfMonth()
                    ]);
                    break;

                case 'last_week':
                    $query->whereBetween('created_at', [
                        Carbon::now()->subWeek()->startOfWeek(),
                        Carbon::now()->subWeek()->endOfWeek()
                    ]);
                    break;

                case 'last_month':
                    $query->whereBetween('created_at', [
                        Carbon::now()->subMonth()->startOfMonth(),
                        Carbon::now()->subMonth()->endOfMonth()
                    ]);
                    break;

                case 'custom_month':
                    if ($request->filled('custom_month')) {
                        $month = Carbon::parse($request->custom_month . '-01');
                        $query->whereBetween('created_at', [
                            $month->startOfMonth(),
                            $month->endOfMonth()
                        ]);
                    }
                    break;
            }
        } else {
            // Handle legacy date filters if date_filter_type is not set
            if ($request->filled('date_from') && $request->filled('date_to')) {
                $query->byDateRange($request->date_from, $request->date_to);
            } elseif ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            } elseif ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
        }

        $auditLogs = $query->recent()->paginate(20)->withQueryString();

        // Bottleneck analysis: average time per step
        $bottleneckAnalysis = $this->calculateBottleneckAnalysis($query);

        // Get filter options
        $users = User::orderBy('first_name')->orderBy('last_name')->get();
        $offices = Office::orderBy('name')->get();
        $divisions = Division::orderBy('name')->get();

        return view('super.audit_logs.index', compact(
            'auditLogs',
            'bottleneckAnalysis',
            'users',
            'offices',
            'divisions'
        ));
    }

    public function show(Request $request, $id)
    {
        $auditLog = AuditLog::with([
            'user',
            'office',
            'division',
            'leaveApplication.employee.user',
            'leaveApplication.leaveType'
        ])->findOrFail($id);

        return view('super.audit_logs.show', compact('auditLog'));
    }

    public function export(Request $request)
    {
        try {
            // Only fetch leave-related audit logs
            $query = AuditLog::with(['user', 'office', 'division', 'leaveApplication'])
                ->whereIn('action_type', ['creation', 'approval', 'cancellation', 'cancellation_request', 'cancellation_action', 'view']);

            // Apply the same date filtering logic as the index method
            if ($request->filled('date_filter_type')) {
                $filterType = $request->date_filter_type;

                switch ($filterType) {
                    case 'specific':
                        if ($request->filled('date_from') && $request->filled('date_to')) {
                            $query->byDateRange($request->date_from, $request->date_to);
                        } elseif ($request->filled('date_from')) {
                            $query->whereDate('created_at', '>=', $request->date_from);
                        } elseif ($request->filled('date_to')) {
                            $query->whereDate('created_at', '<=', $request->date_to);
                        }
                        break;

                    case 'this_week':
                        $query->whereBetween('created_at', [
                            Carbon::now()->startOfWeek(),
                            Carbon::now()->endOfWeek()
                        ]);
                        break;

                    case 'this_month':
                        $query->whereBetween('created_at', [
                            Carbon::now()->startOfMonth(),
                            Carbon::now()->endOfMonth()
                        ]);
                        break;

                    case 'last_week':
                        $query->whereBetween('created_at', [
                            Carbon::now()->subWeek()->startOfWeek(),
                            Carbon::now()->subWeek()->endOfWeek()
                        ]);
                        break;

                    case 'last_month':
                        $query->whereBetween('created_at', [
                            Carbon::now()->subMonth()->startOfMonth(),
                            Carbon::now()->subMonth()->endOfMonth()
                        ]);
                        break;

                    case 'custom_month':
                        if ($request->filled('custom_month')) {
                            $month = Carbon::parse($request->custom_month . '-01');
                            $query->whereBetween('created_at', [
                                $month->startOfMonth(),
                                $month->endOfMonth()
                            ]);
                        }
                        break;
                }
            } else {
                // Handle legacy date filters if date_filter_type is not set
                if ($request->filled('date_from') && $request->filled('date_to')) {
                    $query->byDateRange($request->date_from, $request->date_to);
                } elseif ($request->filled('date_from')) {
                    $query->whereDate('created_at', '>=', $request->date_from);
                } elseif ($request->filled('date_to')) {
                    $query->whereDate('created_at', '<=', $request->date_to);
                }
            }

            $auditLogs = $query->recent()
                ->limit(100)
                ->get();

            \Log::info('Audit log export attempt', [
                'count' => $auditLogs->count(),
                'first_log' => $auditLogs->first() ? $auditLogs->first()->toArray() : null
            ]);

            $pdf = Pdf::loadView('super.audit_logs.pdf.index', compact('auditLogs'))
                ->setPaper('a4', 'landscape');

            return $pdf->stream('audit_logs_' . now()->format('Y_m_d_His') . '.pdf');
        } catch (\Exception $e) {
            \Log::error('Audit log export error: ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
            return back()->withErrors(['export' => 'Failed to export audit logs: ' . $e->getMessage()]);
        }
    }

    /**
     * Calculate bottleneck analysis from audit logs
     */
    private function calculateBottleneckAnalysis($query): array
    {
        // Focus on approval actions with step order
        $approvalLogs = (clone $query)
            ->byActionType('approval')
            ->whereNotNull('step_order')
            ->whereNotNull('leave_application_id')
            ->with('leaveApplication')
            ->get();

        $stepTimes = [];

        foreach ($approvalLogs as $log) {
            if (!$log->leave_application_id || !$log->leaveApplication) {
                continue;
            }

            $stepOrder = $log->step_order;
            $leave = $log->leaveApplication;

            // Calculate time from previous step or filing date
            $previousTime = Carbon::parse($leave->date_filed);

            // Get previous approval at this step or earlier
            $previousApproval = AuditLog::byLeaveApplication($leave->id)
                ->byActionType('approval')
                ->where('step_order', '<', $stepOrder)
                ->orderBy('step_order', 'desc')
                ->first();

            if ($previousApproval) {
                $previousTime = $previousApproval->created_at;
            }

            $hours = $previousTime->diffInHours($log->created_at);

            if (!isset($stepTimes[$stepOrder])) {
                $stepTimes[$stepOrder] = [];
            }
            $stepTimes[$stepOrder][] = $hours;
        }

        // Calculate averages per step
        $stepAnalysis = [];
        foreach ($stepTimes as $stepOrder => $times) {
            if (count($times) > 0) {
                $avgHours = round(array_sum($times) / count($times), 2);
                $stepHours = floor($avgHours);
                $stepMins = round(($avgHours - $stepHours) * 60);
                
                $stepAnalysis[$stepOrder] = [
                    'avg_hours' => $avgHours,
                    'formatted' => $stepHours > 0 ? "{$stepHours}h {$stepMins}m" : "{$stepMins}m",
                    'count' => count($times),
                    'min_hours' => round(min($times), 2),
                    'max_hours' => round(max($times), 2),
                ];
            }
        }

        // Identify slowest step
        $slowestStep = null;
        $maxAvgHours = 0;
        
        foreach ($stepAnalysis as $stepOrder => $data) {
            if ($data['avg_hours'] > $maxAvgHours) {
                $maxAvgHours = $data['avg_hours'];
                $slowestStep = $stepOrder;
            }
        }

        return [
            'step_analysis' => $stepAnalysis,
            'slowest_step' => $slowestStep,
            'slowest_step_data' => $slowestStep !== null ? $stepAnalysis[$slowestStep] : null,
        ];
    }
}