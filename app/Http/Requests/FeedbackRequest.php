<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FeedbackRequest extends FormRequest
{
	public function rules(): array
	{
		return [
			'user_id' => ['required', 'exists:users,id'],
			'body' => ['required'],
			'is_public' => ['boolean'],
		];
	}

	public function authorize(): bool
	{
		return true;
	}
}
