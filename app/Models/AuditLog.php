<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Crypt;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'office_id',
        'division_id',
        'action_type',
        'action',
        'description',
        'step_order',
        'leave_application_id',
        'ip_address',
        'user_agent',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
        'step_order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function leaveApplication(): BelongsTo
    {
        return $this->belongsTo(LeaveApplication::class);
    }

    /**
     * Get decrypted IP address
     */
    public function getDecryptedIpAddressAttribute(): ?string
    {
        try {
            return $this->ip_address ? Crypt::decryptString($this->ip_address) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    // Scope methods for filtering
    public function scopeByStep(Builder $query, int $stepOrder): Builder
    {
        return $query->where('step_order', $stepOrder);
    }

    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    public function scopeByActionType(Builder $query, string $actionType): Builder
    {
        return $query->where('action_type', $actionType);
    }

    public function scopeByDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('created_at', $date);
    }

    public function scopeByDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeByOffice(Builder $query, int $officeId): Builder
    {
        return $query->where('office_id', $officeId);
    }

    public function scopeByDivision(Builder $query, int $divisionId): Builder
    {
        return $query->where('division_id', $divisionId);
    }

    public function scopeByLeaveApplication(Builder $query, int $leaveApplicationId): Builder
    {
        return $query->where('leave_application_id', $leaveApplicationId);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }
}