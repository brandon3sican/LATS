<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Office extends Model
{
    protected $fillable = ['office_code', 'name', 'address'];

    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function approvalSteps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class);
    }

    public function users(): BelongsToMany
    {
        // related_model, pivot_table, foreign_key_of_parent, foreign_key_of_related
        return $this->belongsToMany(User::class, 'employees', 'office_id', 'user_id');
    }
}
