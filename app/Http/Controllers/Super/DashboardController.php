<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\Office;
use App\Models\User;
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
        
        return view('super.dashboard', [
            'stats' => [
                'offices' => Office::count(),
                'users' => $userQuery->count(),
                'admins' => $adminQuery->count(),
                'leaves' => $this->leaveTotals($divisionId),
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
}
