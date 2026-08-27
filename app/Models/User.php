<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @method bool hasRole(string $key)
 * @method bool hasAnyRole(array $keys)
 * @method array roleKeys()
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'last_name',
        'first_name',
        'middle_name',
        'email',
        'password',
        'is_first_login',
        'signature_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getNameAttribute()
    {
        $middle = $this->middle_name ? substr($this->middle_name, 0, 1) . '.' : '';
        return trim("{$this->first_name} {$middle} {$this->last_name}");
    }
    public function getFormalNameAttribute()
    {
        $middle = $this->middle_name ? substr($this->middle_name, 0, 1) . '.' : '';
        return trim("{$this->last_name}, {$this->first_name} {$middle}");
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function hasRole(string $key): bool
    {
        return $this->roles->contains('key', $key);
    }

    public function hasAnyRole(array $keys): bool
    {
        return $this->roles->whereIn('key', $keys)->isNotEmpty();
    }

    public function roleKeys(): array
    {
        return $this->roles->pluck('key')->all();
    }
}
