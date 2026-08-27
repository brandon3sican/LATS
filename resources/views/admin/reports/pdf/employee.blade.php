<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
    .title { font-size: 16px; font-weight: bold; margin-bottom: 2px; }
    .muted { color: #666; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #bbb; padding: 6px; vertical-align: top; }
    th { background: #f2f2f2; text-align: left; }
    .right { text-align: right; }
  </style>
</head>
<body>

  <div class="title">Employee Leave Report</div>
  <div class="muted">
    Employee: <b>{{ $employee->user->name ?? '—' }}</b>
    &nbsp; | &nbsp; Division: <b>{{ $employee->division->name ?? '—' }}</b>
    <br>
    Month: <b>{{ $from->format('F Y') }}</b>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width: 14%;">Date Filed</th>
        <th style="width: 24%;">Type</th>
        <th style="width: 28%;">Dates</th>
        <th style="width: 8%;" class="right">Days</th>
        <th style="width: 12%;">Status</th>
      </tr>
    </thead>
    <tbody>
      @php $grandTotalDays = 0; @endphp
      @foreach($leaves as $l)
        @php
            // CALCULATE DAYS IF 0
            $days = $l->number_of_days;
            if ($days <= 0 && $l->start_date && $l->end_date) {
                $start = \Carbon\Carbon::parse($l->start_date);
                $end = \Carbon\Carbon::parse($l->end_date);
                $days = $start->diffInWeekdays($end->copy()->addDay());
            }
            $grandTotalDays += $days;
        @endphp
      <tr>
        <td>{{ optional($l->date_filed)->format('Y-m-d') }}</td>
        <td>{{ $l->leaveType->name ?? '—' }}</td>
        <td>
          {{ optional($l->start_date)->format('Y-m-d') }}
          to
          {{ optional($l->end_date)->format('Y-m-d') }}
        </td>
        <td class="right">{{ number_format((float)$days, 1) }}</td>
        <td>{{ strtoupper($l->status) }}</td>
      </tr>
      @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" class="right"><strong>Total Days:</strong></td>
            <td class="right"><strong>{{ number_format($grandTotalDays, 1) }}</strong></td>
            <td></td>
        </tr>
    </tfoot>
  </table>

</body>
</html>
