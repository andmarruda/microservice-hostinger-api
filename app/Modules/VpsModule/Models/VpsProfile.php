<?php

namespace App\Modules\VpsModule\Models;

use Illuminate\Database\Eloquent\Model;

class VpsProfile extends Model
{
    protected $fillable = [
        'vps_id',
        'display_name',
        'updated_by',
    ];
}
