<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

#[Fillable(['ritual', 'body', 'tooltip', 'active'])]
class Prompt extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'active' => 'boolean'
    ];

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('active', true);
    }

    #[Scope]
    protected function inactive(Builder $query): void
    {
        $query->where('active', false);
    }

    #[Scope]
    protected function interrupt(Builder $query): void
    {
        $query->where('ritual', 'interrupt');
    }

    #[Scope]
    protected function unused(Builder $query, Collection $usedPromptIds): void
    {
        $query->whereNotIn('id', $usedPromptIds);
    }
}
