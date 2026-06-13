<?php

namespace App\Tools;

use App\Models\Entry;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class FetchEntries implements Tool
{
    /**
     * Create a new FetchEntries tool instance.
     */
    public function __construct(public ?User $user = null)
    {
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Fetch user entries (reflections) within a specific date range.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $query = Entry::query();

        if ($this->user) {
            $query->where('user_id', $this->user->id);
        }

        if ($startDate = $request['start_date'] ?? null) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate = $request['end_date'] ?? null) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $entries = $query->with('prompt')->get();

        if ($entries->isEmpty()) {
            return "No entries found for the specified criteria.";
        }

        return $entries->map(function ($entry) {
            return sprintf(
                "Date: %s\nPrompt: %s\nResponse: %s\n---",
                $entry->created_at->toDateTimeString(),
                $entry->prompt->body,
                $entry->body
            );
        })->implode("\n");
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'start_date' => $schema->string()->description('The start date (YYYY-MM-DD) to fetch entries from.')->required(),
            'end_date' => $schema->string()->description('The end date (YYYY-MM-DD) to fetch entries to.')->required(),
        ];
    }
}
