<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerSettings extends Model
{
    protected $fillable = [
        'settings',
        'value',
        'options',
        'meta',
        'type',
        'update_by',
    ];

    protected $casts = [
        'value' => 'array',
        'options' => 'array',
        'meta' => 'array',
    ];
}