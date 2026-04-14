<?php

namespace App\Modules\SecurityResourceModule\Models;

use App\Modules\AuthModule\Models\User;
use App\Modules\SecurityResourceModule\Factories\SecurityPermissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vps_id',
        'can_manage_firewall',
        'can_manage_ssh_keys',
        'can_manage_snapshots',
        'granted_by',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'can_manage_firewall'  => 'boolean',
            'can_manage_ssh_keys'  => 'boolean',
            'can_manage_snapshots' => 'boolean',
            'expires_at'           => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function canManageFirewall(): bool
    {
        return $this->can_manage_firewall ?? false;
    }

    public function canManageSshKeys(): bool
    {
        return $this->can_manage_ssh_keys ?? false;
    }

    public function canManageSnapshots(): bool
    {
        return $this->can_manage_snapshots ?? false;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grantedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    protected static function newFactory(): SecurityPermissionFactory
    {
        return SecurityPermissionFactory::new();
    }
}
