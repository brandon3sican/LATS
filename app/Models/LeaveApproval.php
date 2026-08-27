<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_application_id',
        'step_order',
        'approver_user_id',
        'action',
        'remarks',
        'acted_at',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    /**
     * Relationship: Link back to the main Leave Application
     */
    public function leave()
    {
        return $this->belongsTo(LeaveApplication::class, 'leave_application_id');
    }

    /**
     * Relationship: The user (approver) who performed the action
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
}
