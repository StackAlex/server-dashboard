<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerNetworkSettings extends Model
{
    protected $fillable = [
        'dns',
        'proxy',
        'docker_proxy',
    ];

    protected $casts = [
        'dns' => 'array',
        'proxy' => 'array',
        'docker_proxy' => 'array',
    ];
}