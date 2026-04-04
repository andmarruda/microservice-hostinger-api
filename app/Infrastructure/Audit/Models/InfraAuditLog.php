<?php

namespace App\Infrastructure\Audit\Models;

use App\Modules\AuthModule\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfraAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'action',
        'actor_id',
        'actor_email',
        'vps_id',
        'resource_type',
        'resource_id',
        'correlation_id',
        'outcome',
        'metadata',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
