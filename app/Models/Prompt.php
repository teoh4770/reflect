<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prompt extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'ritual',
        'body',
        'active'
    ];

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
}
