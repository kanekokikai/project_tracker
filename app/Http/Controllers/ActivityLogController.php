<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;

class ActivityLogController extends Controller
{
    public function index(): JsonResponse
    {
        $logs = ActivityLog::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(ActivityLog::MAX_ENTRIES)
            ->get()
            ->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'event_type' => $log->event_type,
                'author' => $log->author,
                'message' => $log->message,
                'project_id' => $log->project_id,
                'project_name' => $log->project_name,
                'created_at' => $log->created_at?->toIso8601String(),
                'created_at_label' => $this->relativeLabel($log->created_at),
            ]);

        return response()->json([
            'success' => true,
            'logs' => $logs,
        ]);
    }

    private function relativeLabel(?\DateTimeInterface $date): string
    {
        if ($date === null) {
            return '';
        }

        $seconds = now()->diffInSeconds($date);

        if ($seconds < 60) {
            return 'たった今';
        }

        if ($seconds < 3600) {
            return floor($seconds / 60).'分前';
        }

        if ($seconds < 86400) {
            return floor($seconds / 3600).'時間前';
        }

        if ($seconds < 86400 * 7) {
            return floor($seconds / 86400).'日前';
        }

        return $date->format('Y/m/d H:i');
    }
}
