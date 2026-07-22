<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class ActivityLogger
{
    public function record(
        string $eventType,
        string $author,
        string $message,
        ?Project $project = null,
        ?string $projectName = null,
    ): ActivityLog {
        $resolvedAuthor = trim($author) !== '' ? trim($author) : '不明';
        $resolvedName = $projectName ?? $project?->name;

        return DB::transaction(function () use ($eventType, $resolvedAuthor, $message, $project, $resolvedName) {
            $log = ActivityLog::query()->create([
                'event_type' => $eventType,
                'author' => mb_substr($resolvedAuthor, 0, 100),
                'message' => mb_substr($message, 0, 500),
                'project_id' => $project?->id,
                'project_name' => $resolvedName !== null ? mb_substr($resolvedName, 0, 255) : null,
            ]);

            $this->prune();

            return $log;
        });
    }

    private function prune(): void
    {
        $keepIds = ActivityLog::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(ActivityLog::MAX_ENTRIES)
            ->pluck('id');

        if ($keepIds->isEmpty()) {
            return;
        }

        ActivityLog::query()
            ->whereNotIn('id', $keepIds)
            ->delete();
    }
}
