<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerSettings extends Model
{
    protected $fillable = [
        'settings',
        'value',
        'update_by',
    ];
}