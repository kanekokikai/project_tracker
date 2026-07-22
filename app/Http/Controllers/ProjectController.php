<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\ProjectHistory;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function index(Request $request): View
    {
        $departmentFilter = $request->query('department', 'all');

        $query = Project::query()
            ->whereNull('parent_id')
            ->withCount('attachments')
            ->with([
                'children' => fn ($q) => $q->withCount('attachments')->orderByDesc('updated_at'),
                'children.histories' => fn ($q) => $q->orderByDesc('created_at'),
                'histories' => fn ($q) => $q->orderByDesc('created_at'),
            ]);

        if ($departmentFilter && $departmentFilter !== 'all') {
            if ($departmentFilter === '選択なし') {
                $query->where(function ($q) {
                    $q->whereNull('department')
                        ->orWhere('department', '')
                        ->orWhere('department', '選択なし');
                });
            } else {
                $query->where('department', $departmentFilter);
            }
        }

        $parents = $query->get()
            ->sortByDesc(function (Project $project) {
                $latest = $project->histories->max('created_at');

                foreach ($project->children as $child) {
                    $childLatest = $child->histories->max('created_at');

                    if ($childLatest && ($latest === null || $childLatest > $latest)) {
                        $latest = $childLatest;
                    }
                }

                return $latest ?? $project->updated_at;
            })
            ->values();

        return view('projects.index', [
            'parents' => $parents,
            'statuses' => Project::STATUSES,
            'departments' => Project::DEPARTMENTS,
            'departmentFilter' => $departmentFilter,
            'departmentCounts' => $this->departmentCounts(),
            'statusCounts' => $this->statusCounts($parents),
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function departmentCounts(): array
    {
        $parents = Project::query()
            ->whereNull('parent_id')
            ->get(['department']);

        $counts = ['all' => $parents->count()];

        foreach (Project::DEPARTMENTS as $department) {
            if ($department === '選択なし') {
                $counts[$department] = $parents->filter(function (Project $project) {
                    return $project->department === null
                        || $project->department === ''
                        || $project->department === '選択なし';
                })->count();
                continue;
            }

            $counts[$department] = $parents->where('department', $department)->count();
        }

        return $counts;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Project>  $parents
     * @return array<string, int>
     */
    private function statusCounts($parents): array
    {
        $counts = array_fill_keys(Project::STATUSES, 0);
        $counts['all'] = 0;
        $counts['active'] = 0;

        foreach ($parents as $parent) {
            $this->tallyStatusCount($counts, $parent->status);

            foreach ($parent->children as $child) {
                $this->tallyStatusCount($counts, $child->status);
            }
        }

        return $counts;
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function tallyStatusCount(array &$counts, ?string $status): void
    {
        if ($status === null || ! array_key_exists($status, $counts)) {
            return;
        }

        $counts[$status]++;
        $counts['all']++;

        if (in_array($status, Project::ACTIVE_STATUSES, true)) {
            $counts['active']++;
        }
    }

    public function updateStatus(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Project::STATUSES)],
            'author' => ['nullable', 'string', 'max:100'],
        ], [
            'status.required' => 'ステータスを選択してください',
            'status.in' => '無効なステータスです',
        ]);

        $previousStatus = $project->status;

        $project->update([
            'status' => $validated['status'],
            'updated_at' => now(),
        ]);

        if ($previousStatus !== $validated['status']) {
            $this->activityLogger->record(
                ActivityLog::TYPE_STATUS_CHANGED,
                $validated['author'] ?? '不明',
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

    public function show(Project $project): JsonResponse
    {
        return response()->json([
            'success' => true,
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status,
                'team_members' => $project->team_members ?? [],
                'department' => $project->department,
                'parent_id' => $project->parent_id,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:100'],
            'department' => ['nullable', Rule::in(Project::DEPARTMENTS)],
            'team_members' => ['nullable', 'string'],
        ], [
            'name.required' => 'プロジェクト名を入力してください',
            'author.required' => '作成者を入力してください',
        ]);

        $teamMembers = $this->parseTeamMembers($validated['team_members'] ?? '[]');
        $department = $validated['department'] ?? '選択なし';

        DB::transaction(function () use ($validated, $teamMembers, $department) {
            $project = Project::query()->create([
                'name' => $validated['name'],
                'status' => '未着手',
                'team_members' => $teamMembers,
                'department' => $department,
            ]);

            ProjectHistory::query()->create([
                'project_id' => $project->id,
                'author' => $validated['author'],
                'content' => "新規プロジェクト「{$validated['name']}」を作成しました",
            ]);

            $this->activityLogger->record(
                ActivityLog::TYPE_PROJECT_CREATED,
                $validated['author'],
                "プロジェクト「{$validated['name']}」を作成",
                $project,
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'プロジェクトを作成しました',
        ]);
    }

    public function storeSubProject(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:100'],
            'parent_id' => ['required', 'integer', 'exists:projects,id'],
            'team_members' => ['nullable', 'string'],
        ], [
            'name.required' => 'プロジェクト名を入力してください',
            'author.required' => '作成者を入力してください',
            'parent_id.required' => '親プロジェクトが指定されていません',
        ]);

        $parent = Project::query()->findOrFail($validated['parent_id']);

        if (! $parent->isParent()) {
            return response()->json([
                'success' => false,
                'message' => 'サブプロジェクトの親として指定できません',
            ], 422);
        }

        $teamMembers = $this->parseTeamMembers($validated['team_members'] ?? '[]');

        DB::transaction(function () use ($validated, $parent, $teamMembers) {
            $child = Project::query()->create([
                'name' => $validated['name'],
                'parent_id' => $parent->id,
                'status' => '未着手',
                'team_members' => $teamMembers,
            ]);

            ProjectHistory::query()->create([
                'project_id' => $parent->id,
                'author' => $validated['author'],
                'content' => "サブプロジェクト「{$validated['name']}」を作成しました",
            ]);

            $parent->update(['updated_at' => now()]);

            $this->activityLogger->record(
                ActivityLog::TYPE_SUBPROJECT_CREATED,
                $validated['author'],
                "サブプロジェクト「{$validated['name']}」を作成",
                $child,
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'サブプロジェクトを作成しました',
        ]);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'department' => ['nullable', Rule::in(Project::DEPARTMENTS)],
            'team_members' => ['nullable', 'string'],
            'author' => ['nullable', 'string', 'max:100'],
        ], [
            'name.required' => 'プロジェクト名を入力してください',
        ]);

        $author = $validated['author'] ?? '不明';
        $previousName = $project->name;
        $previousDepartment = $project->department ?? '選択なし';
        $previousMembers = array_values($project->team_members ?? []);

        $updateData = [
            'name' => $validated['name'],
            'updated_at' => now(),
        ];

        if ($project->isParent()) {
            $updateData['department'] = $validated['department'] ?? '選択なし';
            $updateData['team_members'] = $this->parseTeamMembers($validated['team_members'] ?? '[]');
        } else {
            $updateData['team_members'] = $this->parseTeamMembers($validated['team_members'] ?? '[]');
        }

        $project->update($updateData);
        $project->refresh();

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

        if ($project->isParent()) {
            $newDepartment = $project->department ?? '選択なし';

            if ($previousDepartment !== $newDepartment) {
                $this->activityLogger->record(
                    ActivityLog::TYPE_DEPARTMENT_CHANGED,
                    $author,
                    "部署を「{$previousDepartment}」から「{$newDepartment}」に変更",
                    $project,
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'プロジェクトを更新しました',
        ]);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'author' => ['nullable', 'string', 'max:100'],
        ]);

        $projectIds = $this->collectProjectIds($project);
        $projectName = $project->name;
        $isSubProject = ! $project->isParent();
        $label = $isSubProject ? 'サブプロジェクト' : 'プロジェクト';

        DB::transaction(function () use ($project, $projectIds, $validated, $projectName, $label) {
            $this->activityLogger->record(
                ActivityLog::TYPE_PROJECT_DELETED,
                $validated['author'] ?? '不明',
                "{$label}「{$projectName}」を削除",
                null,
                $projectName,
            );

            $this->deleteAttachmentDirectories($projectIds);
            $project->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'プロジェクトを削除しました',
        ]);
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

    /**
     * @return list<int>
     */
    private function collectProjectIds(Project $project): array
    {
        $ids = [$project->id];

        $project->loadMissing('children');

        foreach ($project->children as $child) {
            $ids = array_merge($ids, $this->collectProjectIds($child));
        }

        return $ids;
    }

    /**
     * @param  list<int>  $projectIds
     */
    private function deleteAttachmentDirectories(array $projectIds): void
    {
        foreach ($projectIds as $projectId) {
            $directory = public_path("uploads/project_files/{$projectId}");

            if (File::isDirectory($directory)) {
                File::deleteDirectory($directory);
            }
        }
    }
}
