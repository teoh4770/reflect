<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Entry extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'prompt_id',
        'body',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function prompt()
    {
        return $this->belongsTo(Prompt::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
