<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\Division;
use App\Models\LeaveType;
use Illuminate\Support\Facades\Auth;

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

        return view('admin.dashboard', compact('stats', 'divisions', 'chartLabels', 'chartData'));
    }
}
