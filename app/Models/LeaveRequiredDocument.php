<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequiredDocument extends Model
{
    protected $fillable = [
        'leave_type_id','name','key','is_required','rule_json'
    ];

    protected $casts = [
        'rule_json' => 'array',
        'is_required' => 'boolean',
    ];

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
