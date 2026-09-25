<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerSettings extends Model
{
    protected $fillable = [
        'settings',
        'value',
        'type',
        'options',
        'update_by',
    ];

    protected $casts = [
        'value' => 'array',
        'options' => 'array',
    ];
}