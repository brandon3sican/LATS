<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Combined Analysis Report</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0;
            text-transform: uppercase;
        }
        .meta {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #444;
            padding: 8px 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            text-transform: uppercase;
            font-size: 10px;
        }
        .bottleneck {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
        }
        .recommendation {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 10px;
            background-color: #fff;
        }
        .recommendation.high { border-left: 4px solid #dc3545; }
        .recommendation.medium { border-left: 4px solid #ffc107; }
        .recommendation.low { border-left: 4px solid #28a745; }
        .recommendation.info { border-left: 4px solid #17a2b8; }
        .recommendation h4 {
            margin: 0 0 5px 0;
            font-size: 12px;
        }
        .badge {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 3px;
        }
        .badge-high { background-color: #dc3545; color: white; }
        .badge-medium { background-color: #ffc107; color: #333; }
        .badge-low { background-color: #28a745; color: white; }
        .badge-info { background-color: #17a2b8; color: white; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            font-style: italic;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>Combined Analysis Report</h2>
    </div>

    <div class="meta">
        <strong>Office:</strong> {{ $office_name }} <br>
        <strong>Division:</strong> {{ $division_name }} <br>
        <strong>Period:</strong> {{ $from_date }} - {{ $to_date }} <br>
        <strong>Generated:</strong> {{ $generated_at }}
    </div>

    @if($bottleneck_analysis)
    <div class="bottleneck">
        <strong>⚠️ Workflow Bottleneck:</strong> {{ $bottleneck_analysis['step_name'] }} ({{ $bottleneck_analysis['formatted'] }})
    </div>
    @endif

    <h3>Key Metrics</h3>
    <table>
        <tr>
            <th style="width: 50%;">Metric</th>
            <th style="width: 50%;">Value</th>
        </tr>
        <tr>
            <td>Average Approval Time</td>
            <td>{{ $avg_approval_time_formatted }}</td>
        </tr>
        <tr>
            <td>Approval Rate</td>
            <td>{{ $approval_rate }}%</td>
        </tr>
        <tr>
            <td>Total Applications</td>
            <td>{{ $total_applications }}</td>
        </tr>
        <tr>
            <td>Total Audit Logs</td>
            <td>{{ $summary['total_logs'] }}</td>
        </tr>
    </table>

    <h3>Step Response Times</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 40%;">Approval Step</th>
                <th style="width: 30%;">Average Response Time</th>
                <th style="width: 30%;">Total Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($step_response_times as $stepOrder => $stepData)
            <tr>
                <td><strong>Step {{ $stepOrder }}:</strong> {{ $stepData['step_name'] }}</td>
                <td>{{ $stepData['formatted'] }}</td>
                <td class="text-center">{{ $stepData['count'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Audit Actions Summary</h3>
    <table>
        <thead>
            <tr>
                <th>Action Type</th>
                <th class="text-right">Count</th>
                <th class="text-right">Percentage</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary['by_action'] as $action => $count)
            <tr>
                <td>{{ ucfirst($action) }}</td>
                <td class="text-right">{{ $count }}</td>
                <td class="text-right">{{ number_format(($count / $summary['total_logs']) * 100, 1) }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Top Approvers</h3>
    <table>
        <thead>
            <tr>
                <th>Approver</th>
                <th class="text-right">Total Actions</th>
                <th class="text-right">Percentage</th>
            </tr>
        </thead>
        <tbody>
            @foreach(array_slice($approver_performance, 0, 10) as $approver)
            <tr>
                <td>{{ $approver['user_name'] }}</td>
                <td class="text-right">{{ $approver['total_actions'] }}</td>
                <td class="text-right">{{ number_format(($approver['total_actions'] / $summary['total_logs']) * 100, 1) }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Recommendations</h3>
    @forelse($recommendations as $recommendation)
    <div class="recommendation {{ $recommendation['priority'] }}">
        <span class="badge badge-{{ $recommendation['priority'] }}">{{ $recommendation['priority'] }} PRIORITY</span>
        <h4>{{ $recommendation['title'] }}</h4>
        <p>{{ $recommendation['description'] }}</p>
    </div>
    @empty
    <p><em>No specific recommendations at this time. Workflow is performing optimally.</em></p>
    @endforelse

    <div class="footer">
        Generated on {{ $generated_at }} by {{ Auth::user()->name }}
    </div>

</body>
</html>