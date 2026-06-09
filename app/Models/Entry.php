<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entry extends Model
{
    protected $fillable = [
        'prompt_id',
        'body',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];
}
