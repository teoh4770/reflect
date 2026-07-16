<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('feedbacks', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id');
			$table->text('body');
			$table->boolean('is_public')->default(false);
			$table->timestamps();
			$table->softDeletes();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('feedbacks');
	}
};
