<?php

namespace App\Http\Controllers\Approver;

use App\Http\Controllers\Controller;
use App\Models\LeaveApproval;
use App\Models\LeaveApplication;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

use App\Exports\ApproverMyActionsExport;

class ReportController extends Controller
{
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
