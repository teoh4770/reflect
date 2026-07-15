<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'prompt_id', 'body', 'metadata'])]
class Entry extends Model
{
    use HasFactory;

    protected $casts = [
        'metadata' => 'array'
    ];

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(Prompt::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    #[Scope]
    protected function motivationalDrivers(Builder $query): void
    {
        $query->whereHas('prompt', function ($query) {
            $query->motivationalDrivers();
        });
    }
}
