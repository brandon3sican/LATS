<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveApplication extends Model
{
    protected $fillable = [
        'employee_id', 'office_id', 'leave_type_id',
        'date_filed', 'start_date', 'end_date',
        'working_days_requested', 'status', 'current_step_order',
        'details_json', 'commutation',
    ];

    protected $casts = [
        'date_filed' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'details_json' => 'array',
        'working_days_requested' => 'float',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Employee::class, 'employee_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(\App\Models\LeaveType::class, 'leave_type_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(\App\Models\LeaveApproval::class, 'leave_application_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(\App\Models\LeaveAttachment::class, 'leave_application_id');
    }
    public function office()
    {
        return $this->belongsTo(\App\Models\Office::class, 'office_id');
    }
    public function getDetail(string $key)
    {
        return $this->details_json[$key] ?? null;
    }

    // Helper to check leave type code easily
    public function isType(string $code): bool
    {
        return optional($this->leaveType)->code === $code;
    }

}
