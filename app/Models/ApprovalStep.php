<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalStep extends Model
{
    protected $fillable = ['office_id', 'step_order', 'role_key', 'name'];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
