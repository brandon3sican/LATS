<?php

namespace App\Exports;

use App\Models\LeaveApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MonthlyLeaveSummaryExport implements FromCollection, WithHeadings
{
    public function __construct(
        private Request $request,
        private $from,
        private $to
    ) {}

    public function headings(): array
    {
        return [
            'Date Filed', 'Employee', 'Division', 'Leave Type',
            'Start Date', 'End Date', 'Days', 'Status'
        ];
    }

    public function collection(): Collection
    {
        $query = LeaveApplication::with(['employee.user','employee.division','leaveType'])
            ->whereBetween('date_filed', [$this->from, $this->to]);

        if ($this->request->filled('office_id')) {
            $query->where('office_id', $this->request->integer('office_id'));
        }
        if ($this->request->filled('division_id')) {
            $divisionId = $this->request->integer('division_id');
            $query->whereHas('employee', fn($q) => $q->where('division_id', $divisionId));
        }

        return $query->orderBy('date_filed','desc')->get()->map(function($l){
            return [
                optional($l->date_filed)->format('Y-m-d'),
                optional(optional($l->employee)->user)->name,
                optional(optional($l->employee)->division)->name,
                optional($l->leaveType)->name,
                optional($l->start_date)->format('Y-m-d'),
                optional($l->end_date)->format('Y-m-d'),
                $l->number_of_days,
                $l->status,
            ];
        });
    }
}
