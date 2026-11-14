<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Project;

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

        $log = ActivityLog::query()->create([
            'event_type' => $eventType,
            'author' => mb_substr($resolvedAuthor, 0, 100),
            'message' => mb_substr($message, 0, 500),
            'project_id' => $project?->id,
            'project_name' => $resolvedName !== null ? mb_substr($resolvedName, 0, 255) : null,
        ]);

        $this->prune();

        return $log;
    }

    private function prune(): void
    {
        $cutoff = ActivityLog::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->skip(ActivityLog::MAX_ENTRIES)
            ->first(['id', 'created_at']);

        if ($cutoff === null) {
            return;
        }

        ActivityLog::query()
            ->where(function ($query) use ($cutoff) {
                $query->where('created_at', '<', $cutoff->created_at)
                    ->orWhere(function ($sameTime) use ($cutoff) {
                        $sameTime->where('created_at', $cutoff->created_at)
                            ->where('id', '<=', $cutoff->id);
                    });
            })
            ->delete();
    }
}
