<?php

namespace App\Modules\PolicyModule\Models;

use App\Modules\AuthModule\Models\User;
use App\Modules\PolicyModule\Factories\EnforcementPolicyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnforcementPolicy extends Model
{
    /** @use HasFactory<EnforcementPolicyFactory> */
    use HasFactory;

    protected $fillable = [
        'action',
        'scope_type',
        'scope_id',
        'effect',
        'reason',
        'active_from',
        'active_until',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'active_from'  => 'datetime',
            'active_until' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        $now = now();

        if ($this->active_from && $this->active_from->isAfter($now)) {
            return false;
        }

        if ($this->active_until && $this->active_until->isBefore($now)) {
            return false;
        }

        return true;
    }

    protected static function newFactory(): EnforcementPolicyFactory
    {
        return EnforcementPolicyFactory::new();
    }
}
