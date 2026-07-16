<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Feedback extends Model
{
	use SoftDeletes;

	protected $table = 'feedbacks';

	protected $fillable = [
		'user_id',
		'body',
		'is_public',
	];

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

	protected function casts(): array
	{
		return [
			'is_public' => 'boolean',
		];
	}
}
