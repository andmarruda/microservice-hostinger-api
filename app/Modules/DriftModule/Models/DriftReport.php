<?php

namespace App\Modules\DriftModule\Models;

use App\Modules\AuthModule\Models\User;
use App\Modules\DriftModule\Factories\DriftReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriftReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'drift_type',
        'severity',
        'vps_id',
        'user_id',
        'details',
        'status',
        'detected_at',
        'resolved_at',
        'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'details'     => 'array',
            'detected_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    protected static function newFactory(): DriftReportFactory
    {
        return DriftReportFactory::new();
    }
}
