<?php

namespace App\Modules\GovernanceModule\Models;

use App\Modules\AuthModule\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessReviewItem extends Model
{
    protected $fillable = [
        'review_id',
        'user_id',
        'vps_id',
        'decision',
        'decided_at',
        'decided_by',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(AccessReview::class, 'review_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
