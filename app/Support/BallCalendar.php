<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class BallCalendar
{
    private const WEEKDAYS = ['日', '月', '火', '水', '木', '金', '土'];

    /**
     * 未完了ボールを、カレンダー1行ずつにまとめる。
     *
     * @param  list<array{id: int, group_id: int|null, assignee: string, task: string, due: string|null, start: string, project_id: int, project_name: string, subject_name?: string}>  $balls
     * @return array{today: string, dates: list<array<string, mixed>>, rows: list<array<string, mixed>>}
     */
    public static function build(array $balls, string $today): array
    {
        $groups = self::groups($balls);

        if ($groups === []) {
            return [
                'today' => $today,
                'dates' => [],
                'rows' => [],
            ];
        }

        $groups = self::sort($groups, $today);
        [$start, $end] = self::bounds($groups, $today);
        $dates = self::dates($start, $end, $today);
        $index = [];

        foreach ($dates as $position => $date) {
            $index[$date['date']] = $position;
        }

        $rows = [];

        foreach ($groups as $group) {
            $rows[] = [
                'who' => $group['who'],
                'task' => $group['task'],
                'project_id' => $group['project_id'],
                'project_name' => $group['project_name'],
                'room' => self::roomLabel($group['subject_name'], $group['project_name']),
                'cells' => self::cells($dates, $index, $group),
            ];
        }

        return [
            'today' => $today,
            'dates' => $dates,
            'rows' => $rows,
        ];
    }

    /**
     * @param  list<array{id: int, group_id: int|null, assignee: string, task: string, due: string|null, start: string, project_id: int, project_name: string, subject_name?: string}>  $balls
     * @return list<array{id: int, who: string, task: string, due: string|null, start: string, project_id: int, project_name: string, subject_name: string}>
     */
    private static function groups(array $balls): array
    {
        $grouped = [];

        foreach ($balls as $ball) {
            $key = $ball['group_id'] ? 'g'.$ball['group_id'] : 'b'.$ball['id'];
            $grouped[$key][] = $ball;
        }

        $groups = [];

        foreach ($grouped as $items) {
            usort($items, fn (array $a, array $b) => $a['id'] <=> $b['id']);
            $first = $items[0];
            $names = [];
            $start = $first['start'];

            foreach ($items as $item) {
                if (! in_array($item['assignee'], $names, true)) {
                    $names[] = $item['assignee'];
                }

                if ($item['start'] < $start) {
                    $start = $item['start'];
                }
            }

            $groups[] = [
                'id' => $first['id'],
                'who' => implode('、', $names),
                'task' => $first['task'],
                'due' => $first['due'] !== '' ? $first['due'] : null,
                'start' => $start,
                'project_id' => $first['project_id'],
                'project_name' => $first['project_name'],
                'subject_name' => trim((string) ($first['subject_name'] ?? '')),
            ];
        }

        return $groups;
    }

    /**
     * @param  list<array{id: int, who: string, task: string, due: string|null, start: string, project_id: int, project_name: string, subject_name: string}>  $groups
     * @return list<array{id: int, who: string, task: string, due: string|null, start: string, project_id: int, project_name: string, subject_name: string}>
     */
    private static function sort(array $groups, string $today): array
    {
        usort($groups, function (array $a, array $b) use ($today) {
            return self::sortKey($a, $today) <=> self::sortKey($b, $today);
        });

        return $groups;
    }

    /**
     * @param  array{id: int, who: string, task: string, due: string|null, start: string, project_id: int, project_name: string, subject_name: string}  $group
     * @return array{0: int, 1: string, 2: string, 3: string, 4: int}
     */
    private static function sortKey(array $group, string $today): array
    {
        $due = $group['due'];

        if ($due === null) {
            $rank = 2;
            $dueKey = '9999-12-31';
        } elseif ($due < $today) {
            $rank = 0;
            $dueKey = $due;
        } else {
            $rank = 1;
            $dueKey = $due;
        }

        return [$rank, $dueKey, $group['start'], $group['project_name'], $group['id']];
    }

    /**
     * @param  list<array{start: string, due: string|null}>  $groups
     * @return array{0: string, 1: string}
     */
    private static function bounds(array $groups, string $today): array
    {
        $starts = array_column($groups, 'start');
        $left = min($starts);

        if ($left > $today) {
            $left = $today;
        }

        $dues = array_values(array_filter(
            array_column($groups, 'due'),
            fn (?string $due) => $due !== null && $due !== ''
        ));

        if ($dues === []) {
            $right = Carbon::parse($today)->addMonth()->toDateString();
        } else {
            $latest = max($dues);
            $right = $latest > $today ? $latest : $today;
        }

        $latestStart = max($starts);

        if ($latestStart > $right) {
            $right = $latestStart;
        }

        if ($right < $left) {
            $right = $left;
        }

        return [$left, $right];
    }

    public static function roomLabel(string $subject, string $project): string
    {
        $subject = trim($subject);
        $project = trim($project);

        if ($subject === '') {
            return $project;
        }

        return '「'.$subject.'」の「'.$project.'」';
    }

    /**
     * @return list<array{date: string, label: string, weekday: string, weekend: bool, today: bool, month_start: bool}>
     */
    private static function dates(string $start, string $end, string $today): array
    {
        $dates = [];
        $day = Carbon::parse($start)->startOfDay();
        $last = Carbon::parse($end)->startOfDay();
        $first = true;

        while ($day->lte($last)) {
            $value = $day->toDateString();
            $dates[] = [
                'date' => $value,
                'label' => $day->format('n/j'),
                'weekday' => self::WEEKDAYS[$day->dayOfWeek],
                'weekend' => $day->isWeekend(),
                'today' => $value === $today,
                'month_start' => $first || $day->day === 1,
            ];
            $first = false;
            $day->addDay();
        }

        return $dates;
    }

    /**
     * @param  list<array{date: string, weekend: bool, today: bool, month_start: bool}>  $dates
     * @param  array<string, int>  $index
     * @param  array{task: string, due: string|null, start: string}  $group
     * @return list<array<string, mixed>>
     */
    private static function cells(array $dates, array $index, array $group): array
    {
        $barStart = $group['start'];
        $barEnd = $group['due'] ?? $dates[array_key_last($dates)]['date'];

        if ($barEnd < $barStart) {
            $barEnd = $barStart;
        }

        $first = $dates[0]['date'];
        $last = $dates[array_key_last($dates)]['date'];

        if ($barStart < $first) {
            $barStart = $first;
        }

        if ($barEnd > $last) {
            $barEnd = $last;
        }

        $from = $index[$barStart];
        $to = $index[$barEnd];
        $cells = [];
        $position = 0;
        $count = count($dates);

        while ($position < $count) {
            if ($position === $from) {
                $cells[] = [
                    'type' => 'bar',
                    'span' => $to - $from + 1,
                    'task' => $group['task'],
                ];
                $position = $to + 1;

                continue;
            }

            $date = $dates[$position];
            $cells[] = [
                'type' => 'empty',
                'weekend' => $date['weekend'],
                'today' => $date['today'],
                'month_start' => $date['month_start'],
            ];
            $position++;
        }

        return $cells;
    }
}
