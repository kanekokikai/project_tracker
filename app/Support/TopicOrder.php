<?php

namespace App\Support;

use App\Models\Project;
use App\Models\ProjectBall;
use Illuminate\Support\Collection;

class TopicOrder
{
    /**
     * @param  Collection<int, Project>  $projects
     * @return Collection<int, Project>
     */
    public static function sort(Collection $projects, string $viewer): Collection
    {
        return $projects->sortBy(function (Project $project) use ($viewer) {
            return self::keys($project, $viewer);
        })->values();
    }

    /**
     * 会議ログが新しい議題を上にする。ログがない議題は下にまとめる。
     *
     * @param  Collection<int, Project>  $projects
     * @return Collection<int, Project>
     */
    public static function byLatestLog(Collection $projects): Collection
    {
        return $projects->sort(function (Project $left, Project $right) {
            $leftLatest = $left->relationLoaded('latestHistory')
                ? $left->getRelation('latestHistory')
                : null;
            $rightLatest = $right->relationLoaded('latestHistory')
                ? $right->getRelation('latestHistory')
                : null;

            $leftAt = $leftLatest?->created_at?->getTimestamp() ?? 0;
            $rightAt = $rightLatest?->created_at?->getTimestamp() ?? 0;

            if ($leftAt !== $rightAt) {
                return $rightAt <=> $leftAt;
            }

            $leftId = (int) ($leftLatest?->id ?? 0);
            $rightId = (int) ($rightLatest?->id ?? 0);

            if ($leftId !== $rightId) {
                return $rightId <=> $leftId;
            }

            return $right->id <=> $left->id;
        })->values();
    }

    /**
     * @return list<int|string>
     */
    private static function keys(Project $project, string $viewer): array
    {
        $balls = $project->relationLoaded('openBalls')
            ? $project->openBalls
            : $project->openBalls()->get();

        $hasMine = $balls->contains(fn (ProjectBall $ball) => $ball->assignee === $viewer);
        $nonExternal = $balls->filter(fn (ProjectBall $ball) => $ball->ball_type !== '外部');
        $ranked = $nonExternal->isNotEmpty() ? $nonExternal : $balls;

        $today = BallDisplay::today();
        $overdueDays = 0;
        $nearestDue = null;
        $hasDated = false;
        $hasUndated = false;

        foreach ($ranked as $ball) {
            if ($ball->due_date === null) {
                $hasUndated = true;
                continue;
            }

            $hasDated = true;
            $due = $ball->due_date->toDateString();
            $diff = BallDisplay::dayDiff($today, $due);

            if ($ball->ball_type !== '外部' && $diff < 0) {
                $overdueDays = max($overdueDays, -$diff);
            }

            if ($nearestDue === null || $due < $nearestDue) {
                $nearestDue = $due;
            }
        }

        $latest = $project->relationLoaded('latestHistory')
            ? $project->getRelation('latestHistory')
            : null;
        $latestAt = $latest?->created_at;
        $lively = $latestAt !== null && $latestAt->greaterThan(now()->subDays(3));

        if ($balls->isEmpty()) {
            $bucket = $lively ? 3 : 5;
        } elseif ($overdueDays > 0) {
            $bucket = 1;
        } elseif ($hasDated) {
            $bucket = 2;
        } elseif ($lively) {
            $bucket = 3;
        } elseif ($hasUndated) {
            $bucket = 4;
        } else {
            $bucket = 5;
        }

        return [
            $hasMine ? 0 : 1,
            $bucket,
            $bucket === 1 ? -$overdueDays : 0,
            $nearestDue ?? '9999-99-99',
            -($latestAt?->getTimestamp() ?? 0),
            $project->id,
        ];
    }
}
