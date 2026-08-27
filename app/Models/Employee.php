<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'user_id','office_id','division_id','employee_no','position_title','sex','status','salary_grade',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function office(): BelongsTo { return $this->belongsTo(Office::class); }
    public function division(): BelongsTo { return $this->belongsTo(Division::class); }

    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class);
    }
}
