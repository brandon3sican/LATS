<?php

namespace App\Exports;

use App\Models\LeaveApplication;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DivisionLeaveReportExport implements FromCollection, WithHeadings
{
    public function __construct(
        private int $divisionId,
        private $from,
        private $to
    ) {}

    public function headings(): array
    {
        return ['Date Filed','Employee','Leave Type','Start Date','End Date','Days','Status'];
    }

    public function collection(): Collection
    {
        return LeaveApplication::with(['employee.user','leaveType'])
            ->whereHas('employee', fn($q) => $q->where('division_id', $this->divisionId))
            ->whereBetween('date_filed', [$this->from, $this->to])
            ->orderBy('date_filed','desc')
            ->get()
            ->map(fn($l) => [
                optional($l->date_filed)->format('Y-m-d'),
                optional(optional($l->employee)->user)->name,
                optional($l->leaveType)->name,
                optional($l->start_date)->format('Y-m-d'),
                optional($l->end_date)->format('Y-m-d'),
                $l->number_of_days,
                $l->status,
            ]);
    }
}
