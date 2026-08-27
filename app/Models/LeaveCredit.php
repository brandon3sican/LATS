<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'vacation_leave',
        'sick_leave',
        // Add others if needed (e.g., 'special_privilege_leave')
    ];

    /**
     * Get the employee that owns the leave credits.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
