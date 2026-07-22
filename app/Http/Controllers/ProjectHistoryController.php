<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\ProjectHistory;
use App\Services\ActivityLogger;
use App\Services\ChatworkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectHistoryController extends Controller
{
    public function __construct(
        private ChatworkService $chatworkService,
        private ActivityLogger $activityLogger,
    ) {}

    public function show(ProjectHistory $history): JsonResponse
    {
        return response()->json([
            'success' => true,
            'history' => [
                'id' => $history->id,
                'project_id' => $history->project_id,
                'author' => $history->author,
                'status' => $history->status,
                'content' => $history->content,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'author' => ['required', 'string', 'max:100'],
            'content' => ['required', 'string'],
        ]);

        $history = ProjectHistory::query()->create([
            'project_id' => $validated['project_id'],
            'author' => $validated['author'],
            'content' => $validated['content'],
        ]);

        Project::query()
            ->whereKey($validated['project_id'])
            ->update(['updated_at' => now()]);

        $project = Project::query()->find($validated['project_id']);

        if ($project) {
            $this->activityLogger->record(
                ActivityLog::TYPE_COMMENT_ADDED,
                $validated['author'],
                'コメントを追加',
                $project,
            );

            $this->chatworkService->notifyNewComment(
                $project,
                $validated['author'],
                $validated['content'],
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Progress added successfully',
            'history' => $history,
        ]);
    }

    public function update(Request $request, ProjectHistory $history): JsonResponse
    {
        $validated = $request->validate([
            'author' => ['required', 'string', 'max:100'],
            'content' => ['nullable', 'string'],
        ], [
            'author.required' => '名前を入力してください',
        ]);

        $history->update([
            'author' => $validated['author'],
            'content' => $validated['content'] ?? '',
        ]);

        $project = $history->project;

        if ($project) {
            $this->activityLogger->record(
                ActivityLog::TYPE_COMMENT_EDITED,
                $validated['author'],
                'コメントを編集',
                $project,
            );
        }

        return response()->json([
            'success' => true,
            'message' => '履歴が更新されました',
        ]);
    }

    public function destroy(Request $request, ProjectHistory $history): JsonResponse
    {
        $validated = $request->validate([
            'author' => ['nullable', 'string', 'max:100'],
        ]);

        $project = $history->project;
        $author = $validated['author'] ?? $history->author ?? '不明';

        if ($project) {
            $this->activityLogger->record(
                ActivityLog::TYPE_COMMENT_DELETED,
                $author,
                'コメントを削除',
                $project,
            );
        }

        $history->delete();

        return response()->json([
            'success' => true,
            'message' => '履歴が削除されました',
        ]);
    }
}
