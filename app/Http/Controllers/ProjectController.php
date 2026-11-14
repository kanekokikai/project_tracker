<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ChatworkMember;
use App\Models\Project;
use App\Models\ProjectHistory;
use App\Models\Subject;
use App\Services\ActivityLogger;
use App\Support\BallDisplay;
use App\Support\CurrentMember;
use App\Support\TopicOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function index(Request $request): View
    {
        $departmentFilter = (string) $request->query('department', 'all');
        $statusFilter = $this->statusFilter($request);
        $viewer = CurrentMember::name();

        $projects = $this->topicsQuery($departmentFilter, $statusFilter)
            ->with([
                'subject:id,name',
                'openBalls',
            ])
            ->withCount('attachments')
            ->get();

        $this->attachLatestHistories($projects);

        $topics = TopicOrder::byLatestLog($projects)->map(function (Project $project) use ($viewer) {
            $project->setRelation('openBalls', BallDisplay::sortBalls($project->openBalls, $viewer));

            return $project;
        });

        [$departmentCounts, $statusCounts] = $this->summaryCounts($departmentFilter);

        return view('projects.index', [
            'topics' => $topics,
            'subjects' => Subject::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => Project::STATUSES,
            'departments' => Project::DEPARTMENTS,
            'departmentFilter' => $departmentFilter,
            'statusFilter' => $statusFilter,
            'departmentCounts' => $departmentCounts,
            'statusCounts' => $statusCounts,
            'myBallCount' => $topics->sum(function (Project $project) use ($viewer) {
                return $project->openBalls->where('assignee', $viewer)->count();
            }),
            'ballHolders' => $this->ballHolders($topics, $viewer),
            'viewerName' => $viewer,
        ]);
    }

    public function commentHits(Request $request): JsonResponse
    {
        $query = mb_substr($this->normalizeSearchText((string) $request->query('q', '')), 0, 100);

        if ($query === '') {
            return response()->json(['hits' => (object) []]);
        }

        $tokens = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $projects = $this->topicsQuery(
            (string) $request->query('department', 'all'),
            $this->statusFilter($request),
        )->select('projects.id');

        $rows = DB::table('project_history')
            ->select(['id', 'project_id', 'content', 'author'])
            ->whereIn('project_id', $projects)
            ->whereNotNull('content')
            ->where('content', '!=', '')
            ->where(function ($inner) use ($tokens) {
                foreach ($tokens as $token) {
                    $inner->where('content', 'like', '%'.$this->escapeLike($token).'%');
                }
            })
            ->orderByDesc('id')
            ->get();

        $hits = [];

        foreach ($rows as $row) {
            $projectId = (int) $row->project_id;

            if (isset($hits[$projectId])) {
                continue;
            }

            $text = $this->normalizeSearchText((string) $row->content);

            if ($text === '' || ! str_contains($text, $query)) {
                continue;
            }

            $hits[$projectId] = [
                'text' => $this->commentSnippet($text, $query),
                'author' => trim((string) $row->author),
            ];
        }

        return response()->json(['hits' => (object) $hits]);
    }

    public function show(Request $request, Project $project): View|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'status' => $project->status,
                    'team_members' => $project->team_members ?? [],
                    'department' => $project->department,
                    'subject_id' => $project->subject_id,
                ],
            ]);
        }

        $project->load([
            'subject:id,name',
            'openBalls',
            'histories' => fn ($query) => $query->orderBy('created_at')->orderBy('id'),
        ]);

        $viewer = CurrentMember::name();
        $openBalls = BallDisplay::sortBalls($project->openBalls, $viewer);

        $siblings = Project::query()
            ->where('subject_id', $project->subject_id)
            ->whereKeyNot($project->id)
            ->with('openBalls')
            ->get();

        $siblings = TopicOrder::sort($siblings, $viewer)->map(function (Project $sibling) use ($viewer) {
            $sibling->setRelation('openBalls', BallDisplay::sortBalls($sibling->openBalls, $viewer));

            return $sibling;
        });

        $unansweredQuestions = $project->histories
            ->where('kind', ProjectHistory::KIND_QUESTION)
            ->count();

        $logAttachments = $project->attachments()
            ->orderBy('uploaded_at')
            ->orderBy('id')
            ->get();

        $project->setAttribute('attachments_count', $logAttachments->count());

        return view('projects.room', [
            'project' => $project,
            'logAttachments' => $logAttachments,
            'openBalls' => $openBalls,
            'siblings' => $siblings,
            'subjects' => Subject::query()->orderBy('name')->get(['id', 'name']),
            'members' => ChatworkMember::query()->orderBy('sort_order')->orderBy('id')->get(),
            'departments' => Project::DEPARTMENTS,
            'statuses' => Project::STATUSES,
            'viewerName' => $viewer,
            'unansweredQuestions' => $unansweredQuestions,
            'ballTypes' => BallDisplay::TYPES,
            'doneTemplates' => BallDisplay::DONE_TEMPLATES,
            'externalAssignee' => BallDisplay::EXTERNAL,
        ]);
    }

    public function updateStatus(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Project::STATUSES)],
        ], [
            'status.required' => 'ステータスを選択してください',
            'status.in' => '無効なステータスです',
        ]);

        $previousStatus = $project->status;
        $author = CurrentMember::name();

        $project->update([
            'status' => $validated['status'],
            'updated_at' => now(),
        ]);

        if ($previousStatus !== $validated['status']) {
            ProjectHistory::query()->create([
                'project_id' => $project->id,
                'author' => $author,
                'status' => $validated['status'],
                'content' => null,
                'kind' => ProjectHistory::KIND_COMMENT,
            ]);

            $this->activityLogger->record(
                ActivityLog::TYPE_STATUS_CHANGED,
                $author,
                "ステータスを「{$validated['status']}」に変更",
                $project,
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'ステータスを更新しました',
            'status' => $project->status,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'department' => ['nullable', Rule::in(Project::DEPARTMENTS)],
            'team_members' => ['nullable', 'string'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'new_subject_name' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => '議題名を入力してください',
        ]);

        $author = CurrentMember::name();
        $subject = $this->resolveSubject(
            $request,
            $author,
            isset($validated['subject_id']) ? (int) $validated['subject_id'] : null,
        );

        if ($subject === null) {
            $message = '対象を選ぶか、新しい対象の名前を入力してください';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->withErrors(['subject_id' => $message])->withInput();
        }

        $teamMembers = $this->parseTeamMembers($validated['team_members'] ?? '[]');
        $department = $validated['department'] ?? '選択なし';

        $project = DB::transaction(function () use ($validated, $teamMembers, $department, $author, $subject) {
            $project = Project::query()->create([
                'name' => $validated['name'],
                'status' => '進行中',
                'subject_id' => $subject->id,
                'team_members' => $teamMembers,
                'department' => $department,
            ]);

            ProjectHistory::query()->create([
                'project_id' => $project->id,
                'author' => $author,
                'content' => "議題「{$validated['name']}」を作成しました",
                'kind' => ProjectHistory::KIND_COMMENT,
            ]);

            $this->activityLogger->record(
                ActivityLog::TYPE_PROJECT_CREATED,
                $author,
                "議題「{$validated['name']}」を作成",
                $project,
            );

            return $project;
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => '議題を作成しました',
                'id' => $project->id,
                'redirect' => route('projects.show', $project),
            ]);
        }

        return redirect()
            ->route('projects.show', $project)
            ->with('status', '議題を作成しました');
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'department' => ['nullable', Rule::in(Project::DEPARTMENTS)],
            'team_members' => ['nullable', 'string'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'new_subject_name' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => '議題名を入力してください',
        ]);

        $author = CurrentMember::name();
        $previousName = $project->name;
        $previousDepartment = $project->department ?? '選択なし';
        $previousMembers = array_values($project->team_members ?? []);
        $previousSubjectId = $project->subject_id;
        $previousSubjectName = $project->subject?->name;

        $updateData = [
            'name' => $validated['name'],
            'department' => $validated['department'] ?? '選択なし',
            'team_members' => $this->parseTeamMembers($validated['team_members'] ?? '[]'),
            'updated_at' => now(),
        ];

        $newSubjectName = trim((string) ($validated['new_subject_name'] ?? ''));
        $requestedSubjectId = isset($validated['subject_id']) ? (int) $validated['subject_id'] : null;

        if ($newSubjectName !== '' || ($requestedSubjectId && $requestedSubjectId !== $previousSubjectId)) {
            $subject = $this->resolveSubject($request, $author, $requestedSubjectId);

            if ($subject === null) {
                return response()->json([
                    'success' => false,
                    'message' => '対象を選ぶか、新しい対象の名前を入力してください',
                ], 422);
            }

            $updateData['subject_id'] = $subject->id;
        }

        $project->update($updateData);
        $project->refresh()->load('subject');

        if ($previousName !== $project->name) {
            $this->activityLogger->record(
                ActivityLog::TYPE_PROJECT_RENAMED,
                $author,
                "タイトルを「{$previousName}」から「{$project->name}」に変更",
                $project,
            );
        }

        $newMembers = array_values($project->team_members ?? []);

        if ($previousMembers !== $newMembers) {
            $previousLabel = $previousMembers === [] ? 'なし' : implode('、', $previousMembers);
            $newLabel = $newMembers === [] ? 'なし' : implode('、', $newMembers);

            $this->activityLogger->record(
                ActivityLog::TYPE_MEMBERS_CHANGED,
                $author,
                "メンバーを「{$previousLabel}」から「{$newLabel}」に変更",
                $project,
            );
        }

        $newDepartment = $project->department ?? '選択なし';

        if ($previousDepartment !== $newDepartment) {
            $this->activityLogger->record(
                ActivityLog::TYPE_DEPARTMENT_CHANGED,
                $author,
                "部署を「{$previousDepartment}」から「{$newDepartment}」に変更",
                $project,
            );
        }

        if ($previousSubjectId !== $project->subject_id) {
            $newName = $project->subject?->name ?? '';

            $this->activityLogger->record(
                ActivityLog::TYPE_SUBJECT_CHANGED,
                $author,
                "対象を「{$previousSubjectName}」から「{$newName}」に変更",
                $project,
            );
        }

        return response()->json([
            'success' => true,
            'message' => '議題を更新しました',
        ]);
    }

    public function destroy(Project $project): JsonResponse
    {
        $author = CurrentMember::name();
        $projectName = $project->name;

        DB::transaction(function () use ($project, $author, $projectName) {
            $this->activityLogger->record(
                ActivityLog::TYPE_PROJECT_DELETED,
                $author,
                "議題「{$projectName}」を削除",
                null,
                $projectName,
            );

            $directory = public_path("uploads/project_files/{$project->id}");

            if (File::isDirectory($directory)) {
                File::deleteDirectory($directory);
            }

            $project->delete();
        });

        return response()->json([
            'success' => true,
            'message' => '議題を削除しました',
        ]);
    }

    /**
     * @param  Collection<int, Project>  $topics
     * @return Collection<int, array{name: string, label: string, count: int}>
     */
    private function ballHolders(Collection $topics, string $viewer): Collection
    {
        $external = BallDisplay::EXTERNAL;
        $viewerKnown = $viewer !== '' && $viewer !== '不明';
        $counts = $topics
            ->flatMap(fn (Project $project) => $project->openBalls)
            ->filter(fn ($ball) => trim((string) $ball->assignee) !== '')
            ->countBy(fn ($ball) => (string) $ball->assignee);

        $names = ChatworkMember::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('member_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn (string $name) => $name !== '')
            ->unique()
            ->values();

        foreach ($counts->keys() as $name) {
            if (! $names->contains($name)) {
                $names->push((string) $name);
            }
        }

        $order = $names->flip();

        return $names
            ->map(function (string $name) use ($viewer, $viewerKnown, $counts) {
                return [
                    'name' => $name,
                    'label' => $viewerKnown && $name === $viewer ? 'あなた' : $name,
                    'count' => (int) ($counts[$name] ?? 0),
                ];
            })
            ->sortBy(function (array $row) use ($viewer, $viewerKnown, $external, $order) {
                $rank = 1;

                if ($viewerKnown && $row['name'] === $viewer) {
                    $rank = 0;
                } elseif ($row['name'] === $external) {
                    $rank = 2;
                }

                return [$rank, $order[$row['name']] ?? 10000, $row['name']];
            })
            ->values();
    }

    private function statusFilter(Request $request): string
    {
        $status = (string) $request->query('status', '進行中');

        if ($status === 'all' || in_array($status, Project::STATUSES, true)) {
            return $status;
        }

        return '進行中';
    }

    private function topicsQuery(string $departmentFilter, string $statusFilter = 'all')
    {
        $query = Project::query();

        if ($departmentFilter !== '' && $departmentFilter !== 'all') {
            if ($departmentFilter === '選択なし') {
                $query->where(function ($inner) {
                    $inner->whereNull('department')
                        ->orWhere('department', '')
                        ->orWhere('department', '選択なし');
                });
            } else {
                $query->where('department', $departmentFilter);
            }
        }

        if ($statusFilter !== '' && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        return $query;
    }

    /**
     * @return array{0: array<string, int>, 1: array<string, int>}
     */
    private function summaryCounts(string $departmentFilter): array
    {
        $departmentCounts = ['all' => 0];

        foreach (Project::DEPARTMENTS as $department) {
            $departmentCounts[$department] = 0;
        }

        $statusCounts = array_fill_keys(Project::STATUSES, 0);
        $statusCounts['all'] = 0;

        $rows = DB::table('projects')
            ->select(['department', 'status'])
            ->selectRaw('COUNT(*) as total')
            ->groupBy('department', 'status')
            ->get();

        foreach ($rows as $row) {
            $total = (int) $row->total;
            $department = $row->department;

            if ($department === null || $department === '' || $department === '選択なし') {
                $bucket = '選択なし';
            } else {
                $bucket = (string) $department;

                if (! array_key_exists($bucket, $departmentCounts)) {
                    $departmentCounts[$bucket] = 0;
                }
            }

            $departmentCounts[$bucket] += $total;
            $departmentCounts['all'] += $total;

            $inDepartment = $departmentFilter === ''
                || $departmentFilter === 'all'
                || $bucket === $departmentFilter;

            if ($inDepartment && array_key_exists((string) $row->status, $statusCounts)) {
                $statusCounts[(string) $row->status] += $total;
                $statusCounts['all'] += $total;
            }
        }

        return [$departmentCounts, $statusCounts];
    }

    /**
     * @param  Collection<int, Project>  $projects
     */
    private function attachLatestHistories(Collection $projects): void
    {
        $ids = $projects->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        $latestIds = ProjectHistory::query()
            ->selectRaw('MAX(id) as id')
            ->whereIn('project_id', $ids)
            ->groupBy('project_id')
            ->pluck('id');

        $histories = ProjectHistory::query()
            ->whereIn('id', $latestIds)
            ->get(['id', 'project_id', 'content', 'author', 'created_at'])
            ->keyBy('project_id');

        foreach ($projects as $project) {
            $project->setRelation('latestHistory', $histories->get($project->id));
        }
    }

    private function normalizeSearchText(string $value): string
    {
        $collapsed = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        return mb_strtolower($collapsed);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

    private function commentSnippet(string $text, string $query): string
    {
        $limit = 120;

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $pos = mb_stripos($text, $query);
        $start = $pos === false ? 0 : max(0, $pos - 24);
        $slice = mb_substr($text, $start, $limit);
        $prefix = $start > 0 ? '…' : '';
        $suffix = ($start + mb_strlen($slice)) < mb_strlen($text) ? '…' : '';

        return $prefix.$slice.$suffix;
    }

    private function resolveSubject(Request $request, string $author, ?int $subjectId): ?Subject
    {
        $newName = trim((string) $request->input('new_subject_name', ''));

        if ($newName !== '') {
            $existing = Subject::query()->where('name', $newName)->first();

            if ($existing) {
                return $existing;
            }

            $subject = Subject::query()->create([
                'name' => $newName,
                'created_by' => $author,
            ]);

            $this->activityLogger->record(
                ActivityLog::TYPE_SUBJECT_CREATED,
                $author,
                "対象「{$newName}」を作成",
            );

            return $subject;
        }

        if ($subjectId) {
            return Subject::query()->find($subjectId);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function parseTeamMembers(?string $teamMembers): array
    {
        if ($teamMembers === null || $teamMembers === '') {
            return [];
        }

        $decoded = json_decode($teamMembers, true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, fn ($member) => is_string($member) && $member !== ''));
    }
}
