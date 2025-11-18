<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    protected $fillable = [
        'user_id',
        'subacquirer_id',
        'external_id',
        'amount',
        'status',
        'payload',
    ];

    protected $casts = ['payload' => 'array'];
}
