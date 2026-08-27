<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\LeaveApplication;
use App\Models\Office;
use App\Models\User;
use Carbon\CarbonImmutable;
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

    public function index()
    {
        return view('super.dashboard', [
            'stats' => [
                'offices' => Office::count(),
                'users' => User::count(),
                'admins' => User::whereHas('roles', fn ($q) => $q->whereIn('key', ['office_admin', 'admin']))->count(),
                'leaves' => $this->leaveTotals(),
            ],
            'trend' => $this->monthlyTrend(),
        ]);
    }

    /**
     * Overall count per status plus the grand total, in a single aggregate query.
     *
     * @return array<string, int>
     */
    private function leaveTotals(): array
    {
        $query = LeaveApplication::query()->selectRaw('COUNT(*) AS total');

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
     * @return array{labels: list<string>, filed: list<int>, approved: list<int>, disapproved: list<int>}
     */
    private function monthlyTrend(): array
    {
        $start = CarbonImmutable::now()->startOfMonth()->subMonths(self::TREND_MONTHS - 1);

        $period = match (LeaveApplication::query()->getConnection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', date_filed)",
            'pgsql' => "to_char(date_filed, 'YYYY-MM')",
            'sqlsrv' => "FORMAT(date_filed, 'yyyy-MM')",
            default => "DATE_FORMAT(date_filed, '%Y-%m')",
        };

        $rows = LeaveApplication::query()
            ->where('date_filed', '>=', $start->toDateString())
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
