<?php

namespace App\Modules\GovernanceModule\Models;

use App\Modules\AuthModule\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccessReview extends Model
{
    protected $fillable = [
        'reviewer_id',
        'status',
        'period_start',
        'period_end',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'datetime',
            'period_end'   => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AccessReviewItem::class, 'review_id');
    }
}
