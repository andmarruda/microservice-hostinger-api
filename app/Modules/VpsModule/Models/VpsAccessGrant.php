<?php

namespace App\Modules\VpsModule\Models;

use App\Modules\AuthModule\Models\User;
use App\Modules\VpsModule\Factories\VpsAccessGrantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpsAccessGrant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vps_id',
        'granted_by',
        'granted_at',
        'stale_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'stale_at'   => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grantor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    protected static function newFactory(): VpsAccessGrantFactory
    {
        return VpsAccessGrantFactory::new();
    }
}
