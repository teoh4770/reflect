<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entry extends Model
{
    protected $fillable = [
        'user_id',
        'prompt_id',
        'body',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function prompt()
    {
        return $this->belongsTo(Prompt::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
