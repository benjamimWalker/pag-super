<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subacquirer extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'base_url',
        'config'
    ];
    protected $casts = ['config' => 'array'];
}
