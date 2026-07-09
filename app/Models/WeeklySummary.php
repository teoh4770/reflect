<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'week_start', 'week_end', 'identity_snapshot', 'content', 'artifact_type'])]
class WeeklySummary extends Model
{
    protected $casts = [
        'week_start' => 'date',
        'week_end' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
