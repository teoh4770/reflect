<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prompt extends Model
{
    use HasFactory;

    protected $fillable = [
        'ritual',
        'body',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean'
    ];
}
