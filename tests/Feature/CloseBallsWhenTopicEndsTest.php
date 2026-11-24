<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ChatworkMember;
use App\Models\Project;
use App\Models\ProjectBall;
use App\Models\ProjectHistory;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CloseBallsWhenTopicEndsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        URL::forceRootUrl('http://localhost');
    }

    public function test_finishing_a_topic_closes_its_open_balls(): void
    {
        $this->travelTo(Carbon::parse('2026-11-24 09:50:00', 'Asia/Tokyo'));

        $project = $this->topic();
        $open = $this->ball($project, '写真を撮る');
        $alreadyDone = $this->ball($project, '済みの作業', 'done', '2026-11-01 10:00:00');

        $this->asMember()
            ->postJson('/projects/'.$project->id.'/status', ['status' => '完了'])
            ->assertOk()
            ->assertJsonPath('message', 'ステータスを更新し、未完了のボールを閉じました');

        $open->refresh();
        $alreadyDone->refresh();

        $this->assertSame('done', $open->status);
        $this->assertTrue($open->done_at?->equalTo(now()) ?? false);
        $this->assertSame('2026-11-01 10:00:00', $alreadyDone->done_at?->format('Y-m-d H:i:s'));

        $this->assertTrue(
            ProjectHistory::query()
                ->where('project_id', $project->id)
                ->where('status', '完了')
                ->where('content', ProjectHistory::NOTE_CLOSED_BALLS)
                ->exists()
        );
        $this->assertTrue(
            ActivityLog::query()
                ->where('project_id', $project->id)
                ->where('message', 'ステータスを「完了」に変更し、未完了のボールも閉じました')
                ->exists()
        );

        $calendar = $this->get('/ball-calendar')->assertOk()->getContent();
        $this->assertStringNotContainsString('写真を撮る', $calendar);

        $room = $this->asMember()->get('/projects/'.$project->id)->assertOk()->getContent();
        $this->assertStringContainsString('ステータスを「完了」に変更し、未完了のボールも閉じました', $room);
        $this->assertStringNotContainsString('写真を撮る', $room);
    }

    public function test_cancelling_a_topic_closes_its_open_balls(): void
    {
        $project = $this->topic();
        $open = $this->ball($project, '確認する');

        $this->asMember()
            ->postJson('/projects/'.$project->id.'/status', ['status' => '中止'])
            ->assertOk()
            ->assertJsonPath('message', 'ステータスを更新し、未完了のボールを閉じました');

        $this->assertSame('done', $open->refresh()->status);
        $this->assertNotNull($open->done_at);
    }

    public function test_keeping_a_topic_open_leaves_its_balls_open(): void
    {
        $project = $this->topic();
        $open = $this->ball($project, '残す作業');

        $this->asMember()
            ->postJson('/projects/'.$project->id.'/status', ['status' => '進行中'])
            ->assertOk()
            ->assertJsonPath('message', 'ステータスを更新しました');

        $this->assertSame('open', $open->refresh()->status);
        $this->assertNull($open->done_at);

        $calendar = $this->get('/ball-calendar')->assertOk()->getContent();
        $this->assertStringContainsString('残す作業', $calendar);
    }

    public function test_reopening_a_topic_does_not_restore_closed_balls(): void
    {
        $project = $this->topic();
        $open = $this->ball($project, '一度閉じる');

        $this->asMember()
            ->postJson('/projects/'.$project->id.'/status', ['status' => '完了'])
            ->assertOk();

        $this->asMember()
            ->postJson('/projects/'.$project->id.'/status', ['status' => '進行中'])
            ->assertOk()
            ->assertJsonPath('message', 'ステータスを更新しました');

        $this->assertSame('done', $open->refresh()->status);
        $this->assertSame('進行中', $project->refresh()->status);
    }

    private function topic(): Project
    {
        $subject = Subject::query()->create([
            'name' => 'コンベア',
            'created_by' => 'テスト',
        ]);

        return Project::query()->create([
            'name' => '異音',
            'status' => '進行中',
            'subject_id' => $subject->id,
            'team_members' => ['堀内'],
            'department' => '選択なし',
        ]);
    }

    private function ball(Project $project, string $task, string $status = 'open', ?string $doneAt = null): ProjectBall
    {
        return ProjectBall::query()->create([
            'project_id' => $project->id,
            'assignee' => '堀内',
            'ball_type' => '作業',
            'task' => $task,
            'due_date' => '2026-11-30',
            'status' => $status,
            'created_by' => 'テスト',
            'done_at' => $doneAt,
        ]);
    }

    private function asMember(): static
    {
        $member = ChatworkMember::query()->where('member_name', '堀内')->firstOrFail();

        return $this->withSession([
            'app_authenticated' => true,
            'auth_member_id' => $member->id,
            'auth_member_name' => $member->member_name,
        ]);
    }
};
