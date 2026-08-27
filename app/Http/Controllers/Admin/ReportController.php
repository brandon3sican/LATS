<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveApplication;
use App\Models\Employee;
use App\Models\Division;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

use App\Exports\MonthlyLeaveSummaryExport;
use App\Exports\EmployeeLeaveReportExport;
use App\Exports\DivisionLeaveReportExport;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }

    // ==========================================
    // 1. MONTHLY REPORTS
    // ==========================================
    public function monthly(Request $request)
    {
        [$from, $to] = $this->monthRange($request);
        $query = $this->baseLeaveQuery($request)->whereBetween('date_filed', [$from, $to]);
        $leaves = $query->orderBy('date_filed', 'desc')->get();

        $summary = [
            'total' => $leaves->count(),
            'total_days' => (float) $leaves->sum('number_of_days'),
            'by_status' => $leaves->groupBy('status')->map->count()->toArray(),
            'by_type' => $leaves->groupBy(fn($l) => optional($l->leaveType)->name ?? 'Unknown')->map->count()->toArray(),
        ];

        return view('admin.reports.monthly', compact('leaves', 'summary', 'from', 'to'));
    }

    public function monthlyExcel(Request $request)
    {
        [$from, $to] = $this->monthRange($request);

        return Excel::download(
            new MonthlyLeaveSummaryExport($request, $from, $to),
            'monthly_leave_summary_'.$from->format('Y_m').'.xlsx'
        );
    }

    public function monthlyPdf(Request $request)
    {
        [$from, $to] = $this->monthRange($request);
        $query = $this->baseLeaveQuery($request)->whereBetween('date_filed', [$from, $to]);
        $leaves = $query->orderBy('date_filed', 'desc')->get();

        $summary = [
            'total' => $leaves->count(),
            'total_days' => (float) $leaves->sum('number_of_days'),
            'by_status' => $leaves->groupBy('status')->map->count()->toArray(),
            'by_type' => $leaves->groupBy(fn($l) => optional($l->leaveType)->name ?? 'Unknown')->map->count()->toArray(),
        ];

        $pdf = Pdf::loadView('admin.reports.pdf.monthly', compact('leaves', 'summary', 'from', 'to'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('monthly_leave_summary_'.$from->format('Y_m').'.pdf');
    }

    // ==========================================
    // 2. EMPLOYEE REPORTS
    // ==========================================
    public function employee(Request $request)
    {
        [$from, $to] = $this->monthRange($request);
        $adminOfficeId = Auth::user()->employee->office_id ?? abort(403);

        // Fetch ONLY employees in the Admin's Office
        $employees = Employee::with(['user', 'division'])
            ->where('office_id', $adminOfficeId)
            ->get()
            ->sortBy('user.last_name');

        $selectedEmployeeId = $request->integer('employee_id');
        $employee = null;

        if ($selectedEmployeeId) {
            $employee = Employee::with(['user', 'division'])->find($selectedEmployeeId);
            if ($employee && $employee->office_id != $adminOfficeId) {
                abort(403, 'Unauthorized. Employee belongs to a different office.');
            }
        }

        $leaves = collect();
        $totals = null;

        if ($employee) {
            $leaves = LeaveApplication::with(['leaveType'])
                ->where('employee_id', $employee->id)
                ->whereBetween('date_filed', [$from, $to])
                ->orderBy('date_filed', 'desc')
                ->get();

            $totals = [
                'count' => $leaves->count(),
                'days' => (float) $leaves->sum('number_of_days'),
                'by_status' => $leaves->groupBy('status')->map->count()->toArray(),
            ];
        }

        return view('admin.reports.employee', compact('employees', 'employee', 'leaves', 'totals', 'from', 'to'));
    }

    public function employeeExcel(Request $request)
    {
        $employeeId = $request->integer('employee_id');
        $this->authorizeEmployeeReportAccess($employeeId);

        [$from, $to] = $this->monthRange($request);

        return Excel::download(
            new EmployeeLeaveReportExport($employeeId, $from, $to),
            'employee_leave_report_'.$from->format('Y_m').'.xlsx'
        );
    }

    public function employeePdf(Request $request)
    {
        $employeeId = $request->integer('employee_id');
        $this->authorizeEmployeeReportAccess($employeeId);

        [$from, $to] = $this->monthRange($request);
        $employee = Employee::with(['user','division'])->findOrFail($employeeId);

        $leaves = LeaveApplication::with(['leaveType'])
            ->where('employee_id', $employee->id)
            ->whereBetween('date_filed', [$from, $to])
            ->orderBy('date_filed', 'desc')
            ->get();

        $totals = [
            'count' => $leaves->count(),
            'days' => (float) $leaves->sum('number_of_days'),
            'by_status' => $leaves->groupBy('status')->map->count()->toArray(),
        ];

        $pdf = Pdf::loadView('admin.reports.pdf.employee', compact('employee','leaves','totals','from','to'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('employee_leave_report_'.$from->format('Y_m').'.pdf');
    }

    // ==========================================
    // 3. DIVISION REPORTS
    // ==========================================
    public function division(Request $request)
    {
        [$from, $to] = $this->monthRange($request);
        $adminOfficeId = Auth::user()->employee->office_id ?? abort(403);

        // Fetch ONLY divisions in the Admin's Office
        $divisions = Division::where('office_id', $adminOfficeId)->orderBy('name')->get();

        $divisionId = $request->integer('division_id');
        $division = null;

        if ($divisionId) {
            $division = Division::find($divisionId);
            if ($division && $division->office_id != $adminOfficeId) {
                abort(403, 'Unauthorized. Division belongs to a different office.');
            }
        }

        $leaves = collect();
        $totals = null;

        if ($division) {
            $leaves = LeaveApplication::with(['employee.user', 'employee.division', 'leaveType'])
                ->whereHas('employee', fn($q) => $q->where('division_id', $division->id))
                ->whereBetween('date_filed', [$from, $to])
                ->orderBy('date_filed', 'desc')
                ->get();

            $totals = [
                'count' => $leaves->count(),
                'days' => (float) $leaves->sum('number_of_days'),
                'by_status' => $leaves->groupBy('status')->map->count()->toArray(),
                'per_employee' => $leaves->groupBy('employee_id')->map(function($items) {
                    $user = optional(optional($items->first())->employee)->user;
                    return [
                        'name' => $user ? $user->first_name . ' ' . $user->last_name : 'Unknown',
                        'count' => $items->count(),
                        'days' => (float) $items->sum('number_of_days'),
                    ];
                })->values(),
            ];
        }

        return view('admin.reports.division', compact('divisions', 'division', 'leaves', 'totals', 'from', 'to'));
    }

    public function divisionExcel(Request $request)
    {
        $divisionId = $request->integer('division_id');
        $this->authorizeDivisionReportAccess($divisionId);

        [$from, $to] = $this->monthRange($request);

        return Excel::download(
            new DivisionLeaveReportExport($divisionId, $from, $to),
            'division_leave_report_'.$from->format('Y_m').'.xlsx'
        );
    }

    public function divisionPdf(Request $request)
    {
        $divisionId = $request->integer('division_id');
        $this->authorizeDivisionReportAccess($divisionId);

        [$from, $to] = $this->monthRange($request);
        $division = Division::findOrFail($divisionId);

        $leaves = LeaveApplication::with(['employee.user','employee.division','leaveType'])
            ->whereHas('employee', fn($q) => $q->where('division_id', $division->id))
            ->whereBetween('date_filed', [$from, $to])
            ->orderBy('date_filed','desc')
            ->get();

        $totals = [
            'count' => $leaves->count(),
            'days' => (float) $leaves->sum('number_of_days'),
            'by_status' => $leaves->groupBy('status')->map->count()->toArray(),
        ];

        $pdf = Pdf::loadView('admin.reports.pdf.division', compact('division','leaves','totals','from','to'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('division_leave_report_'.$from->format('Y_m').'.pdf');
    }

    // ==========================================
    // 4. FORM 6 PDF (Single Form Print)
    // ==========================================
    public function form6Pdf(int $id)
    {
        $leave = LeaveApplication::with(['employee.user', 'employee.division', 'leaveType', 'office'])->findOrFail($id);
        $adminOfficeId = Auth::user()->employee->office_id ?? abort(403);

        if ($leave->employee->office_id != $adminOfficeId) {
            abort(403, 'Unauthorized viewing of this document.');
        }

        $pdf = Pdf::loadView('pdf.form6_leave', compact('leave'))->setPaper('a4', 'portrait');
        return $pdf->stream('CS_Form_6_'.$leave->id.'.pdf');
    }

    // ==========================================
    // 5. PRIVATE SECURITY / HELPER METHODS
    // ==========================================
    private function authorizeEmployeeReportAccess($employeeId)
    {
        abort_if(!$employeeId, 422, 'employee_id is required');
        $adminOfficeId = Auth::user()->employee->office_id ?? abort(403);
        $employee = Employee::findOrFail($employeeId);
        if ($employee->office_id != $adminOfficeId) {
            abort(403, 'Unauthorized. Employee belongs to another office.');
        }
    }

    private function authorizeDivisionReportAccess($divisionId)
    {
        abort_if(!$divisionId, 422, 'division_id is required');
        $adminOfficeId = Auth::user()->employee->office_id ?? abort(403);
        $division = Division::findOrFail($divisionId);
        if ($division->office_id != $adminOfficeId) {
            abort(403, 'Unauthorized. Division belongs to another office.');
        }
    }

    private function monthRange(Request $request): array
    {
        $year = (int) ($request->input('year') ?? now()->year);
        $month = (int) ($request->input('month') ?? now()->month);
        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to = Carbon::create($year, $month, 1)->endOfMonth();
        return [$from, $to];
    }

    private function baseLeaveQuery(Request $request)
    {
        $adminOfficeId = Auth::user()->employee->office_id ?? abort(403);

        // Locked to admin's office
        $query = LeaveApplication::with(['employee.user', 'employee.division', 'leaveType'])
            ->whereHas('employee', fn($q) => $q->where('office_id', $adminOfficeId));

        if ($request->filled('division_id')) {
            $query->whereHas('employee', fn($q) => $q->where('division_id', $request->integer('division_id')));
        }
        return $query;
    }
}
