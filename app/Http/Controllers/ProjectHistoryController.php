<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\ProjectHistory;
use App\Services\ActivityLogger;
use App\Support\CurrentMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectHistoryController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function show(ProjectHistory $history): JsonResponse
    {
        $history->loadMissing('ball');

        return response()->json([
            'success' => true,
            'history' => [
                'id' => $history->id,
                'project_id' => $history->project_id,
                'author' => $history->author,
                'status' => $history->status,
                'content' => $history->content,
                'can_reopen' => $this->canReopen($history),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer'],
            'content' => ['required', 'string'],
        ], [
            'content.required' => 'コメントを入力してください',
        ]);

        $project = Project::query()->find($validated['project_id']);

        if (! $project) {
            return response()->json([
                'success' => false,
                'message' => '議題が見つかりません',
            ], 404);
        }

        $author = CurrentMember::name();

        $history = $project->histories()->create([
            'author' => $author,
            'content' => $validated['content'],
            'kind' => ProjectHistory::KIND_COMMENT,
        ]);

        $project->touch();

        $this->activityLogger->record(
            ActivityLog::TYPE_COMMENT_ADDED,
            $author,
            'コメントを追加',
            $project,
        );

        return response()->json([
            'success' => true,
            'message' => 'コメントを追加しました',
            'html' => view('projects.partials.history-item', [
                'history' => $history,
                'contentPrefix' => 'content',
                'viewerName' => $author,
            ])->render(),
        ]);
    }

    public function update(Request $request, ProjectHistory $history): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['nullable', 'string'],
        ]);

        $history->update([
            'content' => $validated['content'] ?? '',
        ]);

        $project = $history->project;

        if ($project) {
            $this->activityLogger->record(
                ActivityLog::TYPE_COMMENT_EDITED,
                CurrentMember::name(),
                'コメントを編集',
                $project,
            );
        }

        return response()->json([
            'success' => true,
            'message' => '履歴が更新されました',
        ]);
    }

    public function reopen(ProjectHistory $history): JsonResponse
    {
        $history->loadMissing('ball');

        if (! $this->canReopen($history)) {
            return response()->json([
                'success' => false,
                'message' => 'このコメントからボールは戻せません',
            ], 422);
        }

        $ball = $history->ball;
        $project = $history->project;
        $author = CurrentMember::name();
        $task = $ball->task;

        DB::transaction(function () use ($history, $ball, $project, $author, $task) {
            foreach ($ball->doneSiblings() as $target) {
                $target->update([
                    'status' => 'open',
                    'done_at' => null,
                ]);
            }

            $history->delete();

            if ($project) {
                $project->update(['updated_at' => now()]);
                $this->activityLogger->record(
                    ActivityLog::TYPE_BALL_REOPENED,
                    $author,
                    "ボールを戻した: {$task}",
                    $project,
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'ボールを戻しました',
        ]);
    }

    public function destroy(ProjectHistory $history): JsonResponse
    {
        $project = $history->project;
        $author = CurrentMember::name();

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

    private function canReopen(ProjectHistory $history): bool
    {
        return $history->kind === ProjectHistory::KIND_DONE
            && $history->ball !== null
            && ! $history->ball->isOpen();
    }
}
