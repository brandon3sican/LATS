<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>My Action History</title>
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
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #444;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            text-transform: uppercase;
            font-size: 10px;
        }
        .badge {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }
        .success { color: green; }
        .danger { color: red; }
        .info { color: blue; }
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
        <h2>My Action History Report</h2>
        <p>Approver: {{ Auth::user()->name }}</p>
    </div>

    <div class="meta">
        <strong>Period:</strong> {{ $from->format('F d, Y') }} - {{ $to->format('F d, Y') }} <br>
        <strong>Total Records:</strong> {{ $rows->count() }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 15%;">Date Acted</th>
                <th style="width: 25%;">Employee / Division</th>
                <th style="width: 20%;">Leave Type</th>
                <th style="width: 15%;">Action</th>
                <th style="width: 25%;">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>
                        {{ $row->acted_at->format('Y-m-d') }}<br>
                        <small>{{ $row->acted_at->format('h:i A') }}</small>
                    </td>
                    <td>
                        @if($row->leave && $row->leave->employee && $row->leave->employee->user)
                            <strong>{{ $row->leave->employee->user->name }}</strong><br>
                            <small>{{ $row->leave->employee->division->name ?? '-' }}</small>
                        @else
                            <span style="color:red;">[User Deleted]</span>
                        @endif
                    </td>
                    <td>
                        @if($row->leave && $row->leave->leaveType)
                            {{ $row->leave->leaveType->code }}
                            <br>
                            <small>({{ $row->leave->working_days_requested }} days)</small>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @php
                            $color = match($row->action) {
                                'approved' => 'success',
                                'disapproved' => 'danger',
                                'returned' => 'info',
                                default => ''
                            };
                        @endphp
                        <span class="badge {{ $color }}">
                            {{ strtoupper($row->action) }}
                        </span>
                    </td>
                    <td>
                        {{ $row->remarks ?? '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No records found for this period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('F d, Y h:i A') }} by {{ Auth::user()->name }}
    </div>

</body>
</html>
