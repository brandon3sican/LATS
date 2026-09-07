<?php

namespace App\Http\Controllers\Approver;

use App\Http\Controllers\Controller;
use App\Models\LeaveApproval;
use App\Models\LeaveApplication;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

use App\Exports\ApproverMyActionsExport;

class ReportController extends Controller
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }
    public function index()
    {
        return view('approver.reports.index');
    }

    public function myActions(Request $request)
    {
        $user = $request->user()->loadMissing('employee');
        [$from, $to] = $this->monthRange($request);

        // 'Approved Cancellation' and 'Rejected Cancellation' to the default list
        $actions = (array) $request->input('action', [
            'approved', 'disapproved', 'returned', 'Approved Cancellation', 'Rejected Cancellation'
        ]);

        $rows = LeaveApproval::with([
                'leave.employee.user',
                'leave.employee.division',
                'leave.leaveType',
            ])
            ->where('approver_user_id', $user->id)
            ->whereBetween('acted_at', [$from, $to])
            ->whereIn('action', $actions)
            ->orderBy('acted_at', 'desc')
            ->get();

        // Log report view
        $this->auditLogService->logView(
            $user,
            'approval_report',
            0,
            "Viewed approval actions report for {$from->format('F Y')}",
            $request
        );

        return view('approver.reports.my_actions', compact('rows', 'from', 'to'));
    }

    public function myActionsExcel(Request $request)
    {
        $user = $request->user();
        [$from, $to] = $this->monthRange($request);

        // CHANGED: Added 'Approved Cancellation' and 'Rejected Cancellation'
        $actions = (array) $request->input('action', [
            'approved', 'disapproved', 'returned', 'Approved Cancellation', 'Rejected Cancellation'
        ]);

        // Log Excel export
        $this->auditLogService->logExport(
            $user,
            'approval_actions_report',
            'Excel',
            [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
                'actions' => $actions,
            ],
            $request
        );

        return Excel::download(
            new ApproverMyActionsExport($user->id, $from, $to, $actions),
            'my_actions_'.$from->format('Y_m').'.xlsx'
        );
    }

    public function myActionsPdf(Request $request)
    {
        $user = $request->user();
        [$from, $to] = $this->monthRange($request);

        // CHANGED: Added 'Approved Cancellation' and 'Rejected Cancellation'
        $actions = (array) $request->input('action', [
            'approved', 'disapproved', 'returned', 'Approved Cancellation', 'Rejected Cancellation'
        ]);

        $rows = LeaveApproval::with([
                'leave.employee.user',
                'leave.employee.division',
                'leave.leaveType',
            ])
            ->where('approver_user_id', $user->id)
            ->whereBetween('acted_at', [$from, $to])
            ->whereIn('action', $actions)
            ->orderBy('acted_at', 'desc')
            ->get();

        // Log PDF export
        $this->auditLogService->logExport(
            $user,
            'approval_actions_report',
            'PDF',
            [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
                'actions' => $actions,
            ],
            $request
        );

        $pdf = Pdf::loadView('approver.reports.pdf.my_actions', compact('rows', 'from', 'to'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('my_actions_'.$from->format('Y_m').'.pdf');
    }

    // Approver prints Form 6 (office restriction recommended)
    public function form6Pdf(Request $request, int $id)
    {
        $user = $request->user()->loadMissing('employee');
        abort_if(!$user->employee, 403);

        $leave = LeaveApplication::with([
            'employee.user','employee.division','leaveType','office'
        ])->findOrFail($id);

        abort_if($leave->office_id !== $user->employee->office_id, 403);

        // Log Form 6 access
        $this->auditLogService->logView(
            $user,
            'form6_document',
            $leave->id,
            "Accessed Form 6 for leave application #{$leave->id}",
            $request
        );

        // Check if a cryptographically locked version exists in the vault!
        $lockedPdfPath = storage_path('app/public/locked_leaves/CS_Form_6_' . $leave->id . '.pdf');

        if (file_exists($lockedPdfPath)) {
            return response()->file($lockedPdfPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="CS_Form_6_'.$leave->id.'.pdf"'
            ]);
        }

        // Fallback: Generate an unsigned visual preview if no locked file exists yet
        $pdf = Pdf::loadView('pdf.form6_leave', compact('leave'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('CS_Form_6_'.$leave->id.'.pdf');
    }

    private function monthRange(Request $request): array
    {
        $year = (int) ($request->input('year') ?? now()->year);
        $month = (int) ($request->input('month') ?? now()->month);

        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to = Carbon::create($year, $month, 1)->endOfMonth();

        return [$from, $to];
    }
}
