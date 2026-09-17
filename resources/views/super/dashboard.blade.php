@extends('layouts.app')

@php
    $overviewCards = [
        [
            'title' => 'Registered Offices',
            'value' => data_get($stats, 'offices', 0),
            'variant' => 'primary',
            'route' => 'super.offices.index',
            'linkText' => 'Manage Offices',
        ],
        [
            'title' => 'Total Users',
            'value' => data_get($stats, 'users', 0),
            'variant' => 'success',
            'route' => 'super.users.index',
            'linkText' => 'Manage Users',
        ],
        [
            'title' => 'Office Admins',
            'value' => data_get($stats, 'admins', 0),
            'variant' => 'info',
        ],
        [
            'title' => 'Audit Logs',
            'value' => \App\Models\AuditLog::count(),
            'variant' => 'warning',
            'route' => 'super.audit-logs.index',
            'linkText' => 'View Logs',
        ],
        [
            'title' => 'Reports',
            'value' => 'Generate',
            'variant' => 'info',
            'route' => 'super.reports.generate',
            'linkText' => 'Generate Reports',
        ],
    ];

    $leaveCards = [
        ['title' => 'Total Filed', 'key' => 'total', 'variant' => 'secondary', 'status' => null],
        ['title' => 'Pending', 'key' => 'pending', 'variant' => 'primary', 'status' => 'pending'],
        ['title' => 'Approved', 'key' => 'approved', 'variant' => 'success', 'status' => 'approved'],
        ['title' => 'Returned', 'key' => 'returned', 'variant' => 'warning', 'status' => 'returned'],
        ['title' => 'Disapproved', 'key' => 'disapproved', 'variant' => 'danger', 'status' => 'disapproved'],
    ];

    $breakdown = [
        'Pending' => (int) data_get($stats, 'leaves.pending', 0),
        'Approved' => (int) data_get($stats, 'leaves.approved', 0),
        'Returned' => (int) data_get($stats, 'leaves.returned', 0),
        'Disapproved' => (int) data_get($stats, 'leaves.disapproved', 0),
        'Cancelled' => (int) data_get($stats, 'leaves.cancelled', 0),
    ];

    $trend = $trend ?? ['labels' => [], 'filed' => [], 'approved' => [], 'disapproved' => []];
@endphp

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0">Super Admin Dashboard</h3>
            <div class="d-flex gap-2">
                <form method="GET" action="{{ route('super.dashboard') }}" class="d-flex gap-2">
                    <select name="division_id" class="form-select" style="width: 250px;">
                        <option value="">All Divisions</option>
                        @foreach ($divisions as $division)
                            <option value="{{ $division->id }}" {{ $selectedDivision == $division->id ? 'selected' : '' }}>
                                {{ $division->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
                @if ($selectedDivision)
                    <a href="{{ route('super.dashboard') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </div>
        </div>

        {{-- Overview Cards - Only shown when All Divisions is selected --}}
        @if(!$selectedDivision)
        <div class="row g-3">
            @foreach ($overviewCards as $card)
                <div class="col-12 col-sm-6 col-lg-3 col-xxl">
                    <x-stat-card :title="$card['title']" :value="$card['value']" :variant="$card['variant']" :link="isset($card['route']) && Route::has($card['route']) ? route($card['route']) : null" :link-text="$card['linkText'] ?? null" />
                </div>
            @endforeach
        </div>
        @endif

        <h4 class="mb-3 mt-4">Leave Request Statistics</h4>

        <div class="row g-3">
            @foreach ($leaveCards as $card)
                <div class="col-6 col-lg-3 col-xxl">
                    <x-stat-card :title="$card['title']" :value="data_get($stats, 'leaves.' . $card['key'], 0)" :variant="$card['variant']" :link="$card['status'] && Route::has('super.leaves.index')
                        ? route('super.leaves.index', ['status' => $card['status']])
                        : null" :link-text="$card['status'] ? 'View' : null" />
                </div>
            @endforeach
        </div>

        {{-- Efficiency Metrics Cards --}}
        @php
            $efficiencyMetrics = $stats['efficiency'] ?? null;
        @endphp
        @if($efficiencyMetrics && (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('approver_chief_personnel')))
        <h4 class="mb-3 mt-4">Efficiency Metrics</h4>
        <div class="row g-3">
            {{-- Average Approval Time --}}
            <div class="col-6 col-lg-3 col-xxl">
                <div class="card shadow-sm border-start border-4 border-success h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small text-uppercase fw-bold">Avg Approval Time</div>
                            <div class="fs-1 fw-bold text-dark">{{ $efficiencyMetrics['avg_approval_time_formatted'] }}</div>
                        </div>
                        <i class="bi bi-clock-history fs-1 text-success"></i>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="small text-muted">{{ $efficiencyMetrics['approved_count'] }} approved</div>
                    </div>
                </div>
            </div>

            {{-- Average Step Response Time --}}
            <div class="col-6 col-lg-3 col-xxl">
                <div class="card shadow-sm border-start border-4 border-info h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase fw-bold mb-2">Avg Step Response</div>
                        @if(!empty($efficiencyMetrics['step_response_times']))
                            @foreach($efficiencyMetrics['step_response_times'] as $step => $data)
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small">Step {{ $step }}:</span>
                                    <span class="fw-bold">{{ $data['formatted'] }}</span>
                                </div>
                            @endforeach
                        @else
                            <div class="small text-muted">No step data</div>
                        @endif
                    </div>
                    <div class="card-footer bg-white">
                        <div class="small text-muted">Per approval step</div>
                    </div>
                </div>
            </div>

            {{-- Approval Rate --}}
            <div class="col-6 col-lg-3 col-xxl">
                <div class="card shadow-sm border-start border-4 border-primary h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small text-uppercase fw-bold">Approval Rate</div>
                            <div class="fs-1 fw-bold text-dark">{{ $efficiencyMetrics['approval_rate'] }}%</div>
                        </div>
                        <i class="bi bi-graph-up-arrow fs-1 text-primary"></i>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="small text-muted">{{ $efficiencyMetrics['approved_count'] }}/{{ $efficiencyMetrics['total_applications'] }}</div>
                    </div>
                </div>
            </div>

            {{-- Bottleneck Analysis --}}
            @if($efficiencyMetrics['bottleneck_analysis'])
            <div class="col-6 col-lg-3 col-xxl">
                <div class="card shadow-sm border-start border-4 border-warning h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small text-uppercase fw-bold">Slowest Step</div>
                            <div class="fs-1 fw-bold text-dark">Step {{ $efficiencyMetrics['bottleneck_analysis']['step_order'] }}</div>
                        </div>
                        <i class="bi bi-exclamation-triangle fs-1 text-warning"></i>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="small text-muted">Avg: {{ $efficiencyMetrics['bottleneck_analysis']['formatted'] }}</div>
                    </div>
                </div>
            </div>
            @endif
        </div>
        @endif

        <div class="row g-3 mt-1">
            <div class="col-12 col-xl-8">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Leave Requests Over Time</h5>
                        <div style="position: relative; height: 320px;">
                            <canvas id="leaveTrendChart" role="img"
                                aria-label="Monthly leave requests filed, approved and disapproved"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Status Breakdown</h5>
                        <div style="position: relative; height: 320px;">
                            <canvas id="leaveStatusChart" role="img" aria-label="Leave requests by status"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabular fallback so the monthly figures survive without JavaScript. --}}
        <noscript>
            <table class="table table-sm mt-3">
                <caption>Leave requests by month</caption>
                <thead>
                    <tr>
                        <th scope="col">Month</th>
                        <th scope="col">Filed</th>
                        <th scope="col">Approved</th>
                        <th scope="col">Disapproved</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($trend['labels'] as $i => $label)
                        <tr>
                            <th scope="row">{{ $label }}</th>
                            <td>{{ $trend['filed'][$i] }}</td>
                            <td>{{ $trend['approved'][$i] }}</td>
                            <td>{{ $trend['disapproved'][$i] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </noscript>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"
        integrity="sha256-1G2Xof0CLF+yn6L0Xry8MiAtc67r8HbOX3JI9UmPx9c=" crossorigin="anonymous"></script>
    <script>
        (function () {
            const trend = @json($trend);
            const breakdown = @json($breakdown);

            const line = (label, data, color) => ({
                label,
                data,
                borderColor: color,
                backgroundColor: color + '33',
                tension: 0.3,
                fill: label === 'Filed',
                pointRadius: 3,
            });

            new Chart(document.getElementById('leaveTrendChart'), {
                type: 'line',
                data: {
                    labels: trend.labels,
                    datasets: [
                        line('Filed', trend.filed, '#0d6efd'),
                        line('Approved', trend.approved, '#198754'),
                        line('Disapproved', trend.disapproved, '#dc3545'),
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                    plugins: { legend: { position: 'bottom' } },
                },
            });

            new Chart(document.getElementById('leaveStatusChart'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(breakdown),
                    datasets: [{
                        data: Object.values(breakdown),
                        backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6c757d'],
                        borderWidth: 0,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                },
            });
        })();
    </script>
@endpush
