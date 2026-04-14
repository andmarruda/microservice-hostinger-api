<?php

namespace App\Modules\GovernanceModule\Models;

use App\Modules\AuthModule\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionApproval extends Model
{
    protected $fillable = [
        'requester_id',
        'target_user_id',
        'permission',
        'vps_id',
        'status',
        'approved_by',
        'decided_at',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
