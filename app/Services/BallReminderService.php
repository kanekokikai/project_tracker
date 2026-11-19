<?php

namespace App\Services;

use App\Models\BallReminderSend;
use App\Models\Project;
use App\Models\ProjectBall;
use App\Support\BallDisplay;
use App\Support\BallReminderWindow;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;

class BallReminderService
{
    public function __construct(private ChatworkService $chatwork) {}

    public function send(?CarbonInterface $now = null): int
    {
        $now = ($now ?? now())->copy()->timezone('Asia/Tokyo');
        $slot = BallReminderWindow::slot($now);

        if ($slot === null || ! $this->chatwork->isEnabled()) {
            return 0;
        }

        $today = $now->toDateString();
        $approaching = [];
        $overdue = [];

        foreach ($this->openDatedBalls() as $ball) {
            $due = $ball->due_date?->toDateString();

            if ($due === null) {
                continue;
            }

            if (BallReminderWindow::isOverdue($today, $due)) {
                $overdue[$ball->project_id][] = $ball;
            } elseif (BallReminderWindow::isApproaching($today, $due)) {
                $approaching[$ball->assignee][] = $ball;
            }
        }

        $sent = 0;

        foreach ($approaching as $assignee => $balls) {
            if ($this->deliver($today, $slot, 'approaching', (string) $assignee, function () use ($assignee, $balls) {
                return $this->chatwork->remindApproaching((string) $assignee, $balls);
            })) {
                $sent++;
            }
        }

        foreach ($overdue as $projectId => $balls) {
            $project = $balls[0]->project;

            if ($this->deliver($today, $slot, 'overdue', 'project:'.$projectId, function () use ($project, $balls) {
                return $this->chatwork->remindOverdue($project, $balls, $this->recipients($project, $balls));
            })) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * @return Collection<int, ProjectBall>
     */
    private function openDatedBalls()
    {
        return ProjectBall::query()
            ->where('status', 'open')
            ->whereNotNull('due_date')
            ->where('ball_type', '!=', '外部')
            ->where('assignee', '!=', BallDisplay::EXTERNAL)
            ->whereHas('project', fn ($query) => $query->where('status', '進行中'))
            ->with(['project.subject'])
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  list<ProjectBall>  $balls
     * @return list<string>
     */
    private function recipients(Project $project, array $balls): array
    {
        $names = [];

        foreach (is_array($project->team_members) ? $project->team_members : [] as $name) {
            $this->pushName($names, $name);
        }

        foreach ($balls as $ball) {
            $this->pushName($names, $ball->assignee);
        }

        return array_values($names);
    }

    /**
     * @param  array<string, string>  $names
     */
    private function pushName(array &$names, mixed $name): void
    {
        if (! is_string($name)) {
            return;
        }

        $name = trim($name);

        if ($name === '' || $name === BallDisplay::EXTERNAL) {
            return;
        }

        $names[$name] = $name;
    }

    /**
     * @param  callable(): bool  $send
     */
    private function deliver(string $date, string $slot, string $kind, string $target, callable $send): bool
    {
        try {
            $record = BallReminderSend::query()->create([
                'sent_on' => $date,
                'slot' => $slot,
                'kind' => $kind,
                'target' => $target,
            ]);
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        if ($send()) {
            return true;
        }

        $record->delete();

        return false;
    }
}
