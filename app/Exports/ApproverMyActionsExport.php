<?php

namespace App\Exports;

use App\Models\LeaveApproval;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ApproverMyActionsExport implements FromCollection, WithHeadings
{
    public function __construct(
        private int $userId,
        private $from,
        private $to,
        private array $actions
    ) {}

    public function headings(): array
    {
        return [
            'Acted At','Action','Employee','Division','Leave Type','Dates','Days','Remarks'
        ];
    }

    public function collection(): Collection
    {
        return LeaveApproval::with(['leave.employee.user','leave.employee.division','leave.leaveType'])
            ->where('approver_user_id', $this->userId)
            ->whereBetween('acted_at', [$this->from, $this->to])
            ->whereIn('action', $this->actions)
            ->orderBy('acted_at','desc')
            ->get()
            ->map(function($a){
                $l = $a->leave;
                return [
                    optional($a->acted_at)->format('Y-m-d h:i A'),
                    $a->action,
                    optional(optional($l->employee)->user)->name,
                    optional(optional($l->employee)->division)->name,
                    optional($l->leaveType)->name,
                    optional($l->start_date)->format('Y-m-d').' to '.optional($l->end_date)->format('Y-m-d'),
                    $l->number_of_days ?? '',
                    $a->remarks ?? '',
                ];
            });
    }
}
