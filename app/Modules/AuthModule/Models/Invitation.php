<?php

namespace App\Modules\AuthModule\Models;

use App\Modules\AuthModule\Factories\InvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    /** @use HasFactory<InvitationFactory> */
    use HasFactory;

    protected $fillable = [
        'email',
        'token',
        'invited_by',
        'expires_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * Create a new factory instance for the model.
     * 
     * @return InvitationFactory
     */
    protected static function newFactory(): InvitationFactory
    {
        return InvitationFactory::new();
    }
}
