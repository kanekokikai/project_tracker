<?php

namespace Tests\Unit;

use App\Support\BallCalendar;
use PHPUnit\Framework\TestCase;

class BallCalendarTest extends TestCase
{
    public function test_dated_balls_run_from_the_oldest_throw_through_the_latest_due_date(): void
    {
        $calendar = BallCalendar::build([
            $this->ball(1, 1, '堀内', '写真を撮る', '2026-11-18', '2026-11-25', 10, 'パイプロのグリスについて'),
            $this->ball(2, 1, '星', '写真を撮る', '2026-11-18', '2026-11-25', 10, 'パイプロのグリスについて'),
            $this->ball(3, 1, '川岸', '写真を撮る', '2026-11-18', '2026-11-25', 10, 'パイプロのグリスについて'),
            $this->ball(4, 4, '星', '図面を確認', '2026-11-10', '2026-11-20', 11, '図面の確認'),
        ], '2026-11-17');

        $this->assertSame('2026-11-10', $calendar['dates'][0]['date']);
        $this->assertSame('2026-11-25', $calendar['dates'][array_key_last($calendar['dates'])]['date']);
        $this->assertCount(1, array_filter($calendar['rows'], fn (array $row) => $row['who'] === '堀内、星、川岸'));

        $photo = $this->bar($calendar, 1);
        $this->assertSame('写真を撮る', $photo['task']);
        $this->assertSame('2026-11-18', $photo['from']);
        $this->assertSame('2026-11-25', $photo['to']);
    }

    public function test_overdue_bars_stop_on_the_due_date_and_the_sheet_still_reaches_today(): void
    {
        $calendar = BallCalendar::build([
            $this->ball(1, 1, '堀内', '残件を閉じる', '2026-11-02', '2026-11-10', 8, '残件'),
        ], '2026-11-17');

        $this->assertSame('2026-11-02', $calendar['dates'][0]['date']);
        $this->assertSame('2026-11-17', $calendar['dates'][array_key_last($calendar['dates'])]['date']);

        $bar = $this->bar($calendar, 0);
        $this->assertSame('2026-11-02', $bar['from']);
        $this->assertSame('2026-11-10', $bar['to']);

        $today = array_values(array_filter($calendar['dates'], fn (array $date) => $date['today']))[0];
        $this->assertTrue($today['today']);
        $this->assertSame('火', $today['weekday']);
    }

    public function test_undated_balls_extend_one_month_when_nothing_has_a_due_date(): void
    {
        $calendar = BallCalendar::build([
            $this->ball(1, 1, '堀内', '様子を見る', '2026-11-12', null, 3, '様子見'),
        ], '2026-11-17');

        $this->assertSame('2026-11-12', $calendar['dates'][0]['date']);
        $this->assertSame('2026-12-17', $calendar['dates'][array_key_last($calendar['dates'])]['date']);

        $bar = $this->bar($calendar, 0);
        $this->assertSame('2026-11-12', $bar['from']);
        $this->assertSame('2026-12-17', $bar['to']);
    }

    public function test_mixed_balls_stop_at_the_latest_due_date(): void
    {
        $calendar = BallCalendar::build([
            $this->ball(1, 1, '堀内', '期限あり', '2026-11-10', '2026-11-20', 4, '期限ありの議題'),
            $this->ball(2, 2, '星', '期限なし', '2026-11-05', null, 5, '期限なしの議題'),
        ], '2026-11-17');

        $this->assertSame('2026-11-05', $calendar['dates'][0]['date']);
        $this->assertSame('2026-11-20', $calendar['dates'][array_key_last($calendar['dates'])]['date']);

        $undated = $this->bar($calendar, 1);
        $this->assertSame('期限なし', $undated['task']);
        $this->assertSame('2026-11-05', $undated['from']);
        $this->assertSame('2026-11-20', $undated['to']);
    }

    public function test_rows_are_ordered_overdue_then_upcoming_then_undated(): void
    {
        $calendar = BallCalendar::build([
            $this->ball(1, 1, '堀内', '期限なし', '2026-11-01', null, 1, 'なし'),
            $this->ball(2, 2, '星', '来週', '2026-11-12', '2026-11-24', 2, '来週'),
            $this->ball(3, 3, '川岸', '超過', '2026-11-01', '2026-11-09', 3, '超過'),
            $this->ball(4, 4, '川岸', '近日', '2026-11-14', '2026-11-18', 4, '近日'),
        ], '2026-11-17');

        $this->assertSame(
            ['超過', '近日', '来週', '期限なし'],
            array_column($calendar['rows'], 'task')
        );
    }

    public function test_room_label_joins_the_subject_and_the_topic(): void
    {
        $this->assertSame('「新シリウス」の「テスト」', BallCalendar::roomLabel('新シリウス', 'テスト'));
        $this->assertSame('「新シリウス」の「テスト」', BallCalendar::roomLabel(' 新シリウス ', ' テスト '));
        $this->assertSame('テスト', BallCalendar::roomLabel('', 'テスト'));

        $calendar = BallCalendar::build([
            $this->ball(1, 1, '堀内', 'テストのボール', '2026-11-17', '2026-11-20', 9, 'テスト', '新シリウス'),
        ], '2026-11-17');

        $this->assertSame('「新シリウス」の「テスト」', $calendar['rows'][0]['room']);
    }

    public function test_weekends_are_marked(): void
    {
        $calendar = BallCalendar::build([
            $this->ball(1, 1, '堀内', '作業', '2026-11-17', '2026-11-22', 1, '作業'),
        ], '2026-11-17');

        $byDate = [];

        foreach ($calendar['dates'] as $date) {
            $byDate[$date['date']] = $date;
        }

        $this->assertFalse($byDate['2026-11-20']['weekend']);
        $this->assertTrue($byDate['2026-11-21']['weekend']);
        $this->assertTrue($byDate['2026-11-22']['weekend']);
        $this->assertSame('土', $byDate['2026-11-21']['weekday']);
        $this->assertSame('日', $byDate['2026-11-22']['weekday']);
    }

    /**
     * @return array{from: string, to: string, span: int, task: string}
     */
    private function bar(array $calendar, int $row): array
    {
        $position = 0;

        foreach ($calendar['rows'][$row]['cells'] as $cell) {
            if ($cell['type'] === 'bar') {
                return [
                    'from' => $calendar['dates'][$position]['date'],
                    'to' => $calendar['dates'][$position + $cell['span'] - 1]['date'],
                    'span' => $cell['span'],
                    'task' => $cell['task'],
                ];
            }

            $position++;
        }

        $this->fail('帯がありません');
    }

    /**
     * @return array{id: int, group_id: int|null, assignee: string, task: string, due: string|null, start: string, project_id: int, project_name: string, subject_name: string}
     */
    private function ball(
        int $id,
        ?int $groupId,
        string $assignee,
        string $task,
        string $start,
        ?string $due,
        int $projectId,
        string $projectName,
        string $subjectName = '',
    ): array {
        return [
            'id' => $id,
            'group_id' => $groupId,
            'assignee' => $assignee,
            'task' => $task,
            'due' => $due,
            'start' => $start,
            'project_id' => $projectId,
            'project_name' => $projectName,
            'subject_name' => $subjectName,
        ];
    }
}
