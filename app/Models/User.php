<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $table = 'ap_users';

    protected $fillable = [
        'sso_id', 'name', 'email', 'avatar', 'phone',
        'role', 'is_active', 'is_admin', 'last_login_at',
    ];

    protected $hidden = ['remember_token'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_admin' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public static function roleOptions(): array
    {
        return [
            'sso_admin' => 'SSO Admin',
            'admin' => 'Admin',
            'approval' => 'Approval',
            'user' => 'User',
            'viewer' => 'Viewer',
        ];
    }

    public function isSsoAdmin(): bool
    {
        return $this->role === 'sso_admin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isApproval(): bool
    {
        return $this->role === 'approval';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    public function canCreate(): bool
    {
        return in_array($this->role, ['admin', 'user'], true);
    }

    public function canApprove(): bool
    {
        return in_array($this->role, ['admin', 'approval'], true);
    }

    public function canManageUsers(): bool
    {
        return $this->role === 'sso_admin';
    }

    public function getRoleLabelAttribute(): string
    {
        return self::roleOptions()[$this->role] ?? $this->role;
    }

    public function getRoleColorAttribute(): string
    {
        return match ($this->role) {
            'sso_admin' => 'secondary',
            'admin' => 'primary',
            'approval' => 'warning',
            'user' => 'success',
            default => 'light',
        };
    }
}
