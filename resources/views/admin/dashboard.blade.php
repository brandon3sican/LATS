@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3 class="mb-4">Office Admin Dashboard</h3>

    {{-- Top Stats Row --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-primary h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="text-primary"><i class="bi bi-people me-2"></i>Employees</h5>
                    <h2 class="fw-bold my-3">{{ $stats['employees'] }}</h2>
                    <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-primary btn-sm">Manage</a>
                </div>
            </div>
        </div>

        {{-- Demographics Card --}}
        <div class="col-md-3">
            <div class="card border-info h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="text-info"><i class="bi bi-gender-ambiguous me-2"></i>Demographics</h5>
                    <div class="d-flex justify-content-around my-3">
                        <div class="text-center">
                            <div class="fw-bold fs-4">{{ $stats['male'] ?? 0 }}</div>
                            <small class="text-muted">Male</small>
                        </div>
                        <div class="vr"></div>
                        <div class="text-center">
                            <div class="fw-bold fs-4">{{ $stats['female'] ?? 0 }}</div>
                            <small class="text-muted">Female</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-warning h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="text-warning"><i class="bi bi-hourglass-split me-2"></i>Pending</h5>
                    <h2 class="fw-bold my-3">{{ $stats['pending'] }}</h2>
                    <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-warning btn-sm">Reports</a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-success h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="text-success"><i class="bi bi-check-circle me-2"></i>Approved Today</h5>
                    <h2 class="fw-bold my-3">{{ $stats['approved_today'] }}</h2>
                </div>
            </div>
        </div>
    </div>

    {{-- Efficiency Metrics Row (Admin Only with Division Assignment) --}}
    @if($efficiencyMetrics && auth()->user()->employee && auth()->user()->employee->division_id)
    <div class="row g-3 mb-4">
        {{-- Average Approval Time --}}
        <div class="col-md-4 col-sm-12">
            <div class="card shadow-sm border-start border-4 border-success h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold">Avg Approval Time</div>
                        <div class="fs-1 fw-bold text-dark">{{ $efficiencyMetrics['avg_approval_time_formatted'] }}</div>
                    </div>
                    <i class="bi bi-clock-history fs-1 text-success"></i>
                </div>
                <div class="card-footer bg-white">
                    <div class="small text-muted">
                        Based on {{ $efficiencyMetrics['approved_count'] }} approved applications
                    </div>
                </div>
            </div>
        </div>

        {{-- Average Step Response Time --}}
        <div class="col-md-4 col-sm-12">
            <div class="card shadow-sm border-start border-4 border-info h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold mb-2">Avg Step Response Time</div>
                    @if(!empty($efficiencyMetrics['step_response_times']))
                        @foreach($efficiencyMetrics['step_response_times'] as $step => $data)
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small">Step {{ $step }}:</span>
                                <span class="small fw-bold">{{ $data['formatted'] }}</span>
                            </div>
                        @endforeach
                    @else
                        <div class="small text-muted">No data available</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Approval Rate --}}
        <div class="col-md-4 col-sm-12">
            <div class="card shadow-sm border-start border-4 border-primary h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold">Approval Rate</div>
                        <div class="fs-1 fw-bold text-dark">{{ $efficiencyMetrics['approval_rate'] }}%</div>
                    </div>
                    <i class="bi bi-check-circle fs-1 text-primary"></i>
                </div>
                <div class="card-footer bg-white">
                    <div class="small text-muted">
                        {{ $efficiencyMetrics['approved_count'] }} / {{ $efficiencyMetrics['total_applications'] }} approved
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bottleneck Analysis --}}
    @if($efficiencyMetrics['bottleneck_analysis'])
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-start border-4 border-warning">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                        Bottleneck Analysis
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="alert alert-warning mb-0">
                                <strong>Slowest Approval Step:</strong> Step {{ $efficiencyMetrics['bottleneck_analysis']['step_order'] }}
                                <br>
                                <small>Average Response Time: {{ $efficiencyMetrics['bottleneck_analysis']['formatted'] }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="small text-muted mb-2">Step Performance Overview</h6>
                            @foreach($efficiencyMetrics['step_response_times'] as $step => $data)
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="small">Step {{ $step }}:</span>
                                    <span class="small {{ $step == $efficiencyMetrics['bottleneck_analysis']['step_order'] ? 'text-danger fw-bold' : '' }}">
                                        {{ $data['formatted'] }} ({{ $data['count'] }} actions)
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endif

    {{-- Interactive Chart Row --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="fw-bold text-dark fs-5">
                        <i class="bi bi-bar-chart-fill me-2 text-primary"></i> Total Leaves by Type
                    </div>

                    {{-- DROPDOWN FILTER --}}
                    <div style="min-width: 250px;">
                        <select id="divisionFilter" class="form-select border-primary shadow-sm">
                            <option value="all">All Divisions (Entire Office)</option>
                            @foreach($divisions as $div)
                                <option value="{{ $div->id }}">{{ $div->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="leaveChart" style="min-height: 400px; max-height: 400px;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Load Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('leaveChart').getContext('2d');

        // Data from Controller
        const labels = {!! json_encode($chartLabels ?? []) !!};
        const chartData = {!! json_encode($chartData ?? []) !!};

        // Vibrant colors for the bars
        const barColors = [
            '#0d6efd', '#198754', '#ffc107', '#dc3545',
            '#0dcaf0', '#6f42c1', '#fd7e14', '#20c997',
            '#6c757d', '#e83e8c'
        ];

        // Initialize Chart
        let leaveChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Approved Applications',
                    data: chartData['all'], // Default loads "all"
                    backgroundColor: barColors,
                    borderWidth: 0,
                    borderRadius: 4, // Rounded tops
                    barPercentage: 0.6 // Makes bars slightly slimmer and cleaner
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }, // Hide legend since X-axis has the names
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ` ${context.raw} applications`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false } // Hide vertical grid lines
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 } // Only show whole numbers
                    }
                }
            }
        });

        // Listen for Dropdown Change
        document.getElementById('divisionFilter').addEventListener('change', function(e) {
            const selectedDivision = e.target.value;

            // Update the chart's data array with the selected division's data
            leaveChart.data.datasets[0].data = chartData[selectedDivision];

            // Animate the update
            leaveChart.update();
        });
    });
</script>
@endsection
