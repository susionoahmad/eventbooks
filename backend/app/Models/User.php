<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'telepon',
        'is_active'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // Helper role checks
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isFinance(): bool
    {
        return $this->role === 'finance_manager' || $this->role === 'owner';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->role === 'owner';
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['staff', 'admin', 'finance_manager', 'owner']);
    }
}
