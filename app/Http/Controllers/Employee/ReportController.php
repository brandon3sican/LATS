<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\LeaveApplication;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

use App\Exports\EmployeeMyFormsExport;

class ReportController extends Controller
{
    public function index()
    {
        return view('employee.reports.index');
    }

    public function myForms(Request $request)
    {
        $user = $request->user()->loadMissing('employee');
        abort_if(!$user->employee, 403);

        [$from, $to] = $this->monthRange($request);

        // default: approved + disapproved (rejected). add returned if you want.
        $statuses = $request->input('status', ['approved', 'disapproved']);

        $leaves = LeaveApplication::with(['leaveType'])
            ->where('employee_id', $user->employee->id)
            ->whereBetween('date_filed', [$from, $to])
            ->whereIn('status', (array) $statuses)
            ->orderBy('date_filed', 'desc')
            ->get();

        return view('employee.reports.my_forms', compact('leaves', 'from', 'to'));
    }

    public function myFormsExcel(Request $request)
    {
        $user = $request->user()->loadMissing('employee');
        abort_if(!$user->employee, 403);

        [$from, $to] = $this->monthRange($request);
        $statuses = (array) $request->input('status', ['approved', 'disapproved']);

        return Excel::download(
            new EmployeeMyFormsExport($user->employee->id, $from, $to, $statuses),
            'my_forms_'.$from->format('Y_m').'.xlsx'
        );
    }

    public function myFormsPdf(Request $request)
    {
        $user = $request->user()->loadMissing('employee');
        abort_if(!$user->employee, 403);

        [$from, $to] = $this->monthRange($request);
        $statuses = (array) $request->input('status', ['approved', 'disapproved']);

        $leaves = LeaveApplication::with(['leaveType'])
            ->where('employee_id', $user->employee->id)
            ->whereBetween('date_filed', [$from, $to])
            ->whereIn('status', $statuses)
            ->orderBy('date_filed', 'desc')
            ->get();

        $pdf = Pdf::loadView('employee.reports.pdf.my_forms', compact('leaves', 'from', 'to'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('my_forms_'.$from->format('Y_m').'.pdf');
    }

    // Employee prints Form 6 for their own leave
    public function form6Pdf(Request $request, int $id)
    {
        $user = $request->user()->loadMissing('employee');
        abort_if(!$user->employee, 403);

        $leave = LeaveApplication::with([
            'employee.user', 'employee.division', 'leaveType', 'office'
        ])->findOrFail($id);

        abort_if($leave->employee_id !== $user->employee->id, 403);

        // Optional: only allow print if final status
        // abort_if(!in_array($leave->status, ['approved','disapproved','returned'], true), 403);

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
