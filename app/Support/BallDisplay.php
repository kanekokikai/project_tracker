<?php

namespace App\Support;

use App\Models\ProjectBall;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BallDisplay
{
    public const EXTERNAL = '外部（客先・業者）';

    public const TYPES = ['回答', '確認', '作業', '外部'];

    public const DONE_TEMPLATES = [
        '確認しました',
        '問題ありませんでした',
        'もらいました',
        '完了しました',
    ];

    public static function today(): string
    {
        return now('Asia/Tokyo')->toDateString();
    }

    public static function dayDiff(string $from, string $to): int
    {
        $fromTs = strtotime($from.' 00:00:00');
        $toTs = strtotime($to.' 00:00:00');

        return (int) round(($toTs - $fromTs) / 86400);
    }

    public static function personLabel(string $name): string
    {
        if ($name === self::EXTERNAL || str_ends_with($name, 'さん')) {
            return $name;
        }

        return $name.'さん';
    }

    /**
     * @param  iterable<int, string>  $assignees
     */
    public static function joinNames(iterable $assignees): string
    {
        return collect($assignees)
            ->map(fn ($name) => self::personLabel((string) $name))
            ->implode('と');
    }

    public static function who(ProjectBall $ball, string $viewer): string
    {
        if ($ball->assignee === $viewer) {
            return 'あなた';
        }

        return self::personLabel($ball->assignee);
    }

    /**
     * 同じ投げのボールを、ホームでは一行にまとめる。
     *
     * @param  Collection<int, ProjectBall>  $balls
     * @return Collection<int, Collection<int, ProjectBall>>
     */
    public static function groups(Collection $balls, string $viewer): Collection
    {
        return self::sortBalls($balls, $viewer)
            ->groupBy(fn (ProjectBall $ball) => $ball->group_id ? 'g'.$ball->group_id : 'b'.$ball->id)
            ->map(function (Collection $items) use ($viewer) {
                return $items->sortBy(function (ProjectBall $ball) use ($viewer) {
                    return [
                        $ball->assignee === $viewer ? 0 : 1,
                        $ball->ball_type === '外部' ? 1 : 0,
                        $ball->id,
                    ];
                })->values();
            })
            ->values();
    }

    /**
     * @param  Collection<int, ProjectBall>  $balls
     */
    public static function whoGroup(Collection $balls, string $viewer): string
    {
        return $balls
            ->map(fn (ProjectBall $ball) => self::who($ball, $viewer))
            ->unique()
            ->implode('と');
    }

    /**
     * @param  Collection<int, ProjectBall>  $balls
     */
    public static function displayBall(Collection $balls, string $viewer): ProjectBall
    {
        $own = $balls->first(fn (ProjectBall $ball) => $ball->assignee === $viewer && $ball->ball_type !== '外部');

        if ($own) {
            return $own;
        }

        $internal = $balls->first(fn (ProjectBall $ball) => $ball->ball_type !== '外部');

        if ($internal) {
            return $internal;
        }

        return $balls->first(fn (ProjectBall $ball) => $ball->assignee === $viewer) ?? $balls->first();
    }

    /**
     * @param  Collection<int, ProjectBall>  $balls
     */
    public static function typeOf(Collection $balls): string
    {
        $internal = $balls->first(fn (ProjectBall $ball) => $ball->ball_type !== '外部');

        return $internal->ball_type ?? (string) $balls->first()?->ball_type;
    }

    /**
     * @param  Collection<int, ProjectBall>  $balls
     */
    public static function manageable(Collection $balls, string $viewer): ?ProjectBall
    {
        $own = $balls->first(fn (ProjectBall $ball) => $ball->assignee === $viewer && self::canManage($ball, $viewer));

        if ($own) {
            return $own;
        }

        return $balls->first(fn (ProjectBall $ball) => self::canManage($ball, $viewer));
    }

    public static function dueLabel(ProjectBall $ball): string
    {
        $today = self::today();

        if ($ball->due_date === null) {
            $created = $ball->created_at
                ? $ball->created_at->timezone('Asia/Tokyo')->toDateString()
                : $today;
            $elapsed = max(0, self::dayDiff($created, $today));

            return '期限なし・'.$elapsed.'日経過';
        }

        $due = $ball->due_date->toDateString();
        $diff = self::dayDiff($today, $due);
        $md = Carbon::parse($due)->format('n/j');

        if ($diff < 0) {
            return $md.' を'.(-$diff).'日超過';
        }

        return $md.' あと'.$diff.'日';
    }

    public static function tone(ProjectBall $ball, string $viewer): string
    {
        $classes = [];

        if ($ball->assignee === $viewer) {
            $classes[] = 'ball-mine';
        }

        if ($ball->ball_type === '外部') {
            $classes[] = 'ball-external';

            return implode(' ', $classes);
        }

        if ($ball->due_date === null) {
            return implode(' ', $classes);
        }

        $diff = self::dayDiff(self::today(), $ball->due_date->toDateString());

        if ($diff < 0) {
            $classes[] = 'ball-overdue';
        } elseif ($diff <= 1) {
            $classes[] = 'ball-soon';
        }

        return implode(' ', $classes);
    }

    public static function canManage(ProjectBall $ball, string $viewer): bool
    {
        return $viewer !== '' && $viewer !== '不明'
            && ($ball->assignee === $viewer || $ball->created_by === $viewer);
    }

    /**
     * @param  Collection<int, ProjectBall>  $balls
     * @return Collection<int, ProjectBall>
     */
    public static function sortBalls(Collection $balls, string $viewer): Collection
    {
        $today = self::today();

        return $balls->sortBy(function (ProjectBall $ball) use ($viewer, $today) {
            $mine = $ball->assignee === $viewer ? 0 : 1;
            $diff = $ball->due_date ? self::dayDiff($today, $ball->due_date->toDateString()) : null;

            if ($ball->ball_type === '外部') {
                $group = 4;
            } elseif ($diff !== null && $diff < 0) {
                $group = 1;
            } elseif ($diff !== null) {
                $group = 2;
            } else {
                $group = 3;
            }

            return [$mine, $group, $diff ?? PHP_INT_MAX, $ball->id];
        })->values();
    }

    public static function monthDay(?string $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        return Carbon::parse($date)->format('n/j');
    }
}
