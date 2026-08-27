<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\LeaveApplication;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('employee');
        $employee = $user->employee;

        if (!$user->employee) {
            abort(403, 'No employee profile assigned.');
        }

        $employeeId = $user->employee->id;

        // Recent leaves (for the table)
        $leaves = LeaveApplication::with('leaveType')
            ->where('employee_id', $employeeId)
            ->orderByDesc('date_filed')     // if you have date_filed
            ->orderByDesc('created_at')     // fallback
            ->orderByDesc('id')
            ->take(8)
            ->get();

        // Counts (safe even if you don't use cancelled yet)
        $counts = [
            'total_applications' => LeaveApplication::where('employee_id', $employee->id)->count(),
            'pending'     => LeaveApplication::where('employee_id', $employeeId)->where('status', 'pending')->count(),
            'approved'    => LeaveApplication::where('employee_id', $employeeId)->where('status', 'approved')->count(),
            'returned'    => LeaveApplication::where('employee_id', $employeeId)->where('status', 'returned')->count(),
            'disapproved' => LeaveApplication::where('employee_id', $employeeId)->where('status', 'disapproved')->count(),
            'cancelled'   => LeaveApplication::where('employee_id', $employeeId)->where('status', 'cancelled')->count(),
        ];

        // For blade that expects $stats->pending etc.
        $stats = (object) $counts;

        return view('employee.dashboard', compact('leaves', 'counts', 'stats'));
    }

    /**
     * FullCalendar event source
     * URL: /employee/dashboard/events?start=YYYY-MM-DD&end=YYYY-MM-DD&status=pending
     */
    public function events(Request $request)
    {
        $user = $request->user()->loadMissing('employee');

        if (!$user->employee) {
            abort(403, 'No employee profile assigned.');
        }

        $employeeId = $user->employee->id;

        $start = $request->query('start');
        $end   = $request->query('end');

        $q = LeaveApplication::query()
            ->where('employee_id', $employeeId);

        // Filter by calendar range (overlap)
        if ($start && $end) {
            $startDate = Carbon::parse($start)->toDateString();
            $endDate   = Carbon::parse($end)->toDateString();

            $q->whereDate('start_date', '<=', $endDate)
              ->whereDate('end_date', '>=', $startDate);
        }

        // Optional status filter
        if ($request->filled('status')) {
            $q->where('status', $request->query('status'));
        }

        $leaves = $q->with('leaveType')
            ->orderBy('start_date')
            ->get();

        $events = $leaves->map(function ($l) {
            $status = strtolower($l->status ?? 'pending');

            $startDate = Carbon::parse($l->start_date)->toDateString();

            // FullCalendar expects ALL-DAY end date as exclusive
            $endExclusive = Carbon::parse($l->end_date)->addDay()->toDateString();

            $colors = $this->statusColors($status);

            return [
                'id'    => (string) $l->id,
                'title' => ($l->leaveType->name ?? 'Leave') . ' • ' . strtoupper($status),
                'start' => $startDate,
                'end'   => $endExclusive,
                'allDay' => true,

                // Clicking goes to details
                'url'   => route('employee.leaves.show', $l->id),

                // Styling
                'backgroundColor' => $colors['bg'],
                'borderColor'     => $colors['border'],
                'textColor'       => $colors['text'],
            ];
        });

        return response()->json($events);
    }

    private function statusColors(string $status): array
    {
        // Feel free to tweak these colors later
        return match ($status) {
            'approved'    => ['bg' => '#198754', 'border' => '#198754', 'text' => '#ffffff'],
            'pending'     => ['bg' => '#ffc107', 'border' => '#ffc107', 'text' => '#212529'],
            'returned'    => ['bg' => '#0dcaf0', 'border' => '#0dcaf0', 'text' => '#212529'],
            'disapproved' => ['bg' => '#dc3545', 'border' => '#dc3545', 'text' => '#ffffff'],
            'cancelled'   => ['bg' => '#6c757d', 'border' => '#6c757d', 'text' => '#ffffff'],
            default       => ['bg' => '#6c757d', 'border' => '#6c757d', 'text' => '#ffffff'],
        };
    }
}
