<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerSettings extends Model
{
    protected $fillable = [
        'settings',
        'type',
        'update_by',
    ];

    protected $casts = [
        'value' => 'array',
    ];
}