<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
    .title { font-size: 16px; font-weight: bold; margin-bottom: 2px; }
    .muted { color: #666; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #bbb; padding: 4px; vertical-align: top; }
    th { background: #f2f2f2; text-align: left; font-size: 9px; }
    .right { text-align: right; }
    .badge { padding: 2px 4px; border-radius: 3px; font-size: 8px; }
    .badge-success { background: #d4edda; color: #155724; }
    .badge-danger { background: #f8d7da; color: #721c24; }
    .badge-info { background: #d1ecf1; color: #0c5460; }
    .badge-primary { background: #cce5ff; color: #004085; }
    .badge-secondary { background: #e2e3e5; color: #383d41; }
    .badge-warning { background: #fff3cd; color: #856404; }
  </style>
</head>
<body>

  <div class="title">Audit Logs Report</div>
  <div class="muted">
    Generated: <b>{{ now()->format('F d, Y H:i:s') }}</b>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width: 10%;">Date/Time</th>
        <th style="width: 15%;">User</th>
        <th style="width: 10%;">Action Type</th>
        <th style="width: 10%;">Action</th>
        <th style="width: 20%;">Description</th>
        <th style="width: 5%;">Step</th>
        <th style="width: 12%;">Office</th>
        <th style="width: 12%;">Division</th>
        <th style="width: 6%;">IP Address</th>
      </tr>
    </thead>
    <tbody>
      @forelse($auditLogs as $log)
        @php
            $badgeClass = match($log->action_type) {
                'approval' => 'badge-success',
                'cancellation' => 'badge-danger',
                'view' => 'badge-info',
                'export' => 'badge-primary',
                'dashboard' => 'badge-secondary',
                'inbox' => 'badge-warning',
                default => 'badge-secondary',
            };
        @endphp
      <tr>
        <td>
          <div>{{ $log->created_at->format('M d, Y') }}</div>
          <div class="muted">{{ $log->created_at->format('H:i:s') }}</div>
        </td>
        <td>
          <div>{{ $log->user ? $log->user->name : 'N/A' }}</div>
          <div class="muted">{{ $log->user ? $log->user->email : '' }}</div>
        </td>
        <td>
          <span class="badge {{ $badgeClass }}">{{ ucfirst($log->action_type) }}</span>
        </td>
        <td>{{ ucfirst($log->action) }}</td>
        <td>
          <div>{{ $log->description }}</div>
          @if($log->leave_application_id)
            <div class="muted">Leave #{{ $log->leave_application_id }}</div>
          @endif
        </td>
        <td>
          @if($log->step_order)
            <span class="badge badge-info">{{ $log->step_order }}</span>
          @else
            <span class="muted">-</span>
          @endif
        </td>
        <td>{{ $log->office ? $log->office->name : 'N/A' }}</td>
        <td>{{ $log->division ? $log->division->name : 'N/A' }}</td>
        <td>{{ $log->ip_address ?? 'N/A' }}</td>
      </tr>
      @empty
        <tr>
          <td colspan="9" class="text-center py-4 muted">No audit logs found.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div class="muted" style="margin-top: 20px;">
    Total Records: <b>{{ $auditLogs->count() }}</b>
  </div>

</body>
</html>