<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ChatworkMember;
use App\Models\Project;
use App\Models\ProjectBall;
use App\Models\ProjectHistory;
use App\Services\ActivityLogger;
use App\Services\ChatworkService;
use App\Support\BallDisplay;
use App\Support\CurrentMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProjectBallController extends Controller
{
    public function __construct(
        private ActivityLogger $activityLogger,
        private ChatworkService $chatworkService,
    ) {}

    public function store(Request $request, Project $project): JsonResponse
    {
        $allowedAssignees = ChatworkMember::query()
            ->orderBy('sort_order')
            ->pluck('member_name')
            ->push(BallDisplay::EXTERNAL)
            ->unique()
            ->values()
            ->all();

        $validated = $request->validate([
            'assignees' => ['required', 'array', 'min:1'],
            'assignees.*' => ['required', 'string', Rule::in($allowedAssignees)],
            'ball_type' => ['required', Rule::in(BallDisplay::TYPES)],
            'task' => ['required', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
            'no_due' => ['nullable', 'boolean'],
        ], [
            'assignees.required' => '誰に投げるか選んでください',
            'assignees.min' => '誰に投げるか選んでください',
            'ball_type.required' => '待ちの種類を選んでください',
            'task.required' => '何をしてほしいか書いてください',
        ]);

        $noDue = $request->boolean('no_due');
        $dueDate = $noDue ? null : ($validated['due_date'] ?? null);

        if (! $noDue && $dueDate === null) {
            return response()->json([
                'success' => false,
                'message' => '期限を選ぶか、期限なしにしてください',
            ], 422);
        }

        $author = CurrentMember::name();
        $task = trim($validated['task']);
        $assignees = array_values(array_unique($validated['assignees']));
        $created = [];

        DB::transaction(function () use ($project, $assignees, $validated, $dueDate, $author, $task, &$created) {
            foreach ($assignees as $assignee) {
                $type = $assignee === BallDisplay::EXTERNAL ? '外部' : $validated['ball_type'];

                $created[] = ProjectBall::query()->create([
                    'project_id' => $project->id,
                    'assignee' => $assignee,
                    'ball_type' => $type,
                    'task' => $task,
                    'due_date' => $dueDate,
                    'status' => 'open',
                    'created_by' => $author,
                ]);
            }

            $groupId = $created[0]->id;

            ProjectBall::query()
                ->whereIn('id', array_map(fn (ProjectBall $ball) => $ball->id, $created))
                ->update(['group_id' => $groupId]);

            foreach ($created as $ball) {
                $ball->group_id = $groupId;
            }

            $dueLabel = $dueDate
                ? '期限 '.BallDisplay::monthDay($dueDate)
                : '期限なし';
            $who = BallDisplay::joinNames($assignees);

            ProjectHistory::query()->create([
                'project_id' => $project->id,
                'author' => $author,
                'content' => "{$who}へ: {$task}（{$dueLabel}）",
                'kind' => ProjectHistory::KIND_BALL,
                'ball_id' => $groupId,
            ]);

            $this->activityLogger->record(
                ActivityLog::TYPE_BALL_THROWN,
                $author,
                "{$who}へボールを投げた: {$task}",
                $project,
            );

            $project->update(['updated_at' => now()]);
        });

        $chatwork = $this->chatworkService;

        app()->terminating(function () use ($chatwork, $project, $created) {
            $chatwork->notifyBallsAssigned($project, $created);
        });

        return response()->json([
            'success' => true,
            'message' => 'ボールを投げました',
        ]);
    }

    public function done(Request $request, ProjectBall $ball): JsonResponse
    {
        if (! $ball->isOpen()) {
            return response()->json([
                'success' => false,
                'message' => 'このボールは既に閉じられています',
            ], 422);
        }

        $author = CurrentMember::name();

        if (! BallDisplay::canManage($ball, $author)) {
            return response()->json([
                'success' => false,
                'message' => 'このボールを閉じられるのは、受け取った人と投げた人だけです',
            ], 403);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ], [
            'content.required' => 'できたことを書いてください',
        ]);

        $content = trim($validated['content']);

        if ($content === '') {
            return response()->json([
                'success' => false,
                'message' => 'できたことを書いてください',
            ], 422);
        }

        $project = $ball->project;
        $closed = false;

        DB::transaction(function () use ($ball, $project, $author, $content, &$closed) {
            $locked = ProjectBall::query()->whereKey($ball->id)->lockForUpdate()->first();

            if ($locked === null || ! $locked->isOpen()) {
                return;
            }

            $targets = $locked->group_id === null
                ? collect([$locked])
                : ProjectBall::query()
                    ->where('group_id', $locked->group_id)
                    ->where('status', 'open')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

            if ($targets->isEmpty()) {
                return;
            }

            $now = now();

            foreach ($targets as $target) {
                $target->update([
                    'status' => 'done',
                    'done_at' => $now,
                ]);
            }

            $others = $targets->where('id', '!=', $locked->id)->pluck('assignee');
            $note = $content;

            if ($others->isNotEmpty()) {
                $note .= "\n".BallDisplay::joinNames($others).'のボールも、この完了で閉じました';
            }

            ProjectHistory::query()->create([
                'project_id' => $locked->project_id,
                'author' => $author,
                'content' => $note,
                'kind' => ProjectHistory::KIND_DONE,
                'ball_id' => $locked->id,
            ]);

            $summary = "ボールを閉じた: {$locked->task}";

            if ($others->isNotEmpty()) {
                $summary .= '（'.BallDisplay::joinNames($others).'の分も）';
            }

            $this->activityLogger->record(
                ActivityLog::TYPE_BALL_DONE,
                $author,
                $summary,
                $project,
            );

            $project?->update(['updated_at' => now()]);
            $closed = true;
        });

        if (! $closed) {
            return response()->json([
                'success' => false,
                'message' => 'このボールは既に閉じられています',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'ボールを閉じました',
        ]);
    }

    public function extend(Request $request, ProjectBall $ball): JsonResponse
    {
        if (! $ball->isOpen()) {
            return response()->json([
                'success' => false,
                'message' => '閉じたボールの期限は変えられません',
            ], 422);
        }

        $author = CurrentMember::name();

        if (! BallDisplay::canManage($ball, $author)) {
            return response()->json([
                'success' => false,
                'message' => '期限を延ばせるのは、受け取った人と投げた人だけです',
            ], 403);
        }

        $validated = $request->validate([
            'due_date' => ['required', 'date'],
        ], [
            'due_date.required' => '延ばす日を選んでください',
        ]);

        $newDue = $validated['due_date'];
        $oldDue = $ball->due_date?->toDateString();

        if ($oldDue !== null && $newDue <= $oldDue) {
            return response()->json([
                'success' => false,
                'message' => '今の期限より後の日を選んでください',
            ], 422);
        }

        $project = $ball->project;
        $oldLabel = $oldDue ? BallDisplay::monthDay($oldDue) : null;
        $newLabel = BallDisplay::monthDay($newDue);
        $message = $oldLabel
            ? "期限を {$oldLabel} から {$newLabel} に延ばしました"
            : "期限を {$newLabel} にしました";

        DB::transaction(function () use ($ball, $project, $author, $newDue, $message) {
            foreach ($ball->openSiblings() as $target) {
                $target->update(['due_date' => $newDue]);
            }

            ProjectHistory::query()->create([
                'project_id' => $ball->project_id,
                'author' => $author,
                'content' => $message,
                'kind' => ProjectHistory::KIND_COMMENT,
                'ball_id' => $ball->id,
            ]);

            $this->activityLogger->record(
                ActivityLog::TYPE_BALL_DUE_CHANGED,
                $author,
                $message.' / '.$ball->task,
                $project,
            );

            $project?->update(['updated_at' => now()]);
        });

        return response()->json([
            'success' => true,
            'message' => '期限を延ばしました',
        ]);
    }
}
