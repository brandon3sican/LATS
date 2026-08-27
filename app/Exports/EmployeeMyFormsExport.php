<?php

namespace App\Exports;

use App\Models\LeaveApplication;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EmployeeMyFormsExport implements FromCollection, WithHeadings
{
    public function __construct(
        private int $employeeId,
        private $from,
        private $to,
        private array $statuses
    ) {}

    public function headings(): array
    {
        return ['Date Filed','Leave Type','Start Date','End Date','Days','Status'];
    }

    public function collection(): Collection
    {
        return LeaveApplication::with(['leaveType'])
            ->where('employee_id', $this->employeeId)
            ->whereBetween('date_filed', [$this->from, $this->to])
            ->whereIn('status', $this->statuses)
            ->orderBy('date_filed','desc')
            ->get()
            ->map(fn($l) => [
                optional($l->date_filed)->format('Y-m-d'),
                optional($l->leaveType)->name,
                optional($l->start_date)->format('Y-m-d'),
                optional($l->end_date)->format('Y-m-d'),
                $l->number_of_days,
                $l->status,
            ]);
    }
}
