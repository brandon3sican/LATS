<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Audit Trail Report</title>
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
        <h2>Audit Trail Report</h2>
    </div>

    <div class="meta">
        <strong>Office:</strong> {{ $office_name }} <br>
        <strong>Division:</strong> {{ $division_name }} <br>
        <strong>Period:</strong> {{ $from_date }} - {{ $to_date }} <br>
        <strong>Generated:</strong> {{ $generated_at }}
    </div>

    <h3>Summary</h3>
    <table>
        <tr>
            <th style="width: 50%;">Metric</th>
            <th style="width: 50%;">Value</th>
        </tr>
        <tr>
            <td>Total Audit Logs</td>
            <td>{{ $summary['total_logs'] }}</td>
        </tr>
        <tr>
            <td>Active Users</td>
            <td>{{ $summary['by_user']->count() }}</td>
        </tr>
        <tr>
            <td>Action Types</td>
            <td>{{ $summary['by_action']->count() }}</td>
        </tr>
    </table>

    <h3>Actions by Type</h3>
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

    <h3>Top Approvers by Activity</h3>
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

    <h3>Recent Audit Log Entries</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 15%;">Date/Time</th>
                <th style="width: 25%;">User</th>
                <th style="width: 15%;">Action</th>
                <th style="width: 30%;">Description</th>
                <th style="width: 15%;">Step</th>
            </tr>
        </thead>
        <tbody>
            @foreach($audit_logs->take(50) as $log)
            <tr>
                <td>
                    {{ $log->created_at->format('Y-m-d') }}<br>
                    <small>{{ $log->created_at->format('h:i A') }}</small>
                </td>
                <td>
                    @if($log->user && $log->user->employee)
                        {{ $log->user->employee->full_name }}
                    @else
                        {{ $log->user->name ?? 'Unknown' }}
                    @endif
                </td>
                <td>{{ ucfirst($log->action_type) }}</td>
                <td>{{ $log->description }}</td>
                <td>
                    @if($log->step_order)
                        Step {{ $log->step_order }}
                    @else
                        -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($audit_logs->count() > 50)
    <p><em>Showing first 50 of {{ $audit_logs->count() }} audit log entries.</em></p>
    @endif

    <div class="footer">
        Generated on {{ $generated_at }} by {{ Auth::user()->name }}
    </div>

</body>
</html>