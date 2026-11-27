<?php

namespace Tests\Feature;

use App\Models\ChatworkMember;
use App\Models\Project;
use App\Models\ProjectBall;
use App\Models\ProjectHistory;
use App\Models\Subject;
use App\Support\BallDisplay;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RoomRenewalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        URL::forceRootUrl('http://localhost');
    }

    public function test_a_ball_is_thrown_from_the_message_without_choosing_a_type(): void
    {
        $project = $this->topic();

        $this->asMember()->postJson('/projects/'.$project->id.'/balls', [
            'assignees' => ['堀内', '金子'],
            'task' => '機械を動かして確認してほしい',
            'due_date' => '2026-11-30',
        ])->assertOk();

        $balls = ProjectBall::query()->orderBy('id')->get();
        $this->assertCount(2, $balls);
        $this->assertSame(['作業', '作業'], $balls->pluck('ball_type')->all());
        $this->assertSame(
            "堀内さんと金子さんへ · 11/30まで\n機械を動かして確認してほしい",
            ProjectHistory::query()->where('kind', ProjectHistory::KIND_BALL)->value('content')
        );

        $room = $this->asMember()->get('/projects/'.$project->id)->assertOk()->getContent();

        $this->assertStringContainsString('堀内さんと金子さんへ · 11/30まで', $room);
        $this->assertStringContainsString('機械を動かして確認してほしい', $room);
        $this->assertStringContainsString('id="roomOpenBalls"', $room);
        $this->assertStringContainsString('>堀内<', $room);
        $this->assertStringContainsString('>金子<', $room);
        $this->assertStringNotContainsString('name="ball_type"', $room);
        $this->assertStringNotContainsString('id="throwBallForm"', $room);
        $this->assertStringNotContainsString('>ボード<', $room);
    }

    public function test_an_outside_ball_does_not_ask_for_a_type(): void
    {
        $project = $this->topic();

        $this->asMember()->postJson('/projects/'.$project->id.'/balls', [
            'assignees' => [BallDisplay::EXTERNAL],
            'task' => '客先の返事を待つ',
            'no_due' => true,
        ])->assertOk();

        $ball = ProjectBall::query()->first();
        $this->assertSame('外部', $ball->ball_type);
        $this->assertNull($ball->due_date);
    }

    public function test_a_due_ball_is_ready_to_answer_and_leaves_the_fixed_list_when_returned(): void
    {
        $this->travelTo(Carbon::parse('2026-11-27 10:00:00', 'Asia/Tokyo'));
        $project = $this->topic();

        $this->asMember()->postJson('/projects/'.$project->id.'/balls', [
            'assignees' => ['堀内'],
            'task' => '機械を動かして確認してほしい',
            'due_date' => '2026-11-25',
        ])->assertOk();

        $before = $this->asMember()->get('/projects/'.$project->id)->assertOk()->getContent();
        $this->assertStringContainsString('data-auto-return="1"', $before);

        $ball = ProjectBall::query()->first();

        $this->asMember()->postJson('/balls/'.$ball->id.'/done', [
            'content' => '動きました。問題なし。',
        ])->assertOk();

        $after = $this->asMember()->get('/projects/'.$project->id)->assertOk()->getContent();
        $this->assertStringContainsString('返ってきた · 堀内さんへ · 11/25まで', $after);
        $this->assertStringContainsString('返事 · 機械を動かして確認してほしい', $after);
        $this->assertStringContainsString('動きました。問題なし。', $after);
        $this->assertStringNotContainsString('id="roomOpenBalls"', $after);
    }

    public function test_a_ball_before_its_deadline_is_not_preselected(): void
    {
        $this->travelTo(Carbon::parse('2026-11-20 10:00:00', 'Asia/Tokyo'));
        $project = $this->topic();
        $this->ball($project, '来週見る', '2026-11-30');

        $room = $this->asMember()->get('/projects/'.$project->id)->assertOk()->getContent();

        $this->assertStringContainsString('id="roomOpenBalls"', $room);
        $this->assertStringNotContainsString('data-auto-return="1"', $room);
    }

    public function test_the_fourth_open_ball_is_collapsed(): void
    {
        $project = $this->topic();
        $this->ball($project, '一つ', '2026-11-20');
        $this->ball($project, '二つ', '2026-11-21');
        $this->ball($project, '三つ', '2026-11-22');
        $this->ball($project, '四つ', '2026-11-28');

        $room = $this->asMember()->get('/projects/'.$project->id)->assertOk()->getContent();
        $this->assertStringContainsString('ほか1件', $room);

        [$visible, $extra] = explode('id="roomOpenMore"', $room, 2);
        $this->assertStringNotContainsString('四つ', $visible);
        $this->assertStringContainsString('四つ', $extra);
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
            'team_members' => ['堀内', '金子'],
            'department' => '選択なし',
        ]);
    }

    private function ball(Project $project, string $task, string $due): ProjectBall
    {
        return ProjectBall::query()->create([
            'project_id' => $project->id,
            'assignee' => '堀内',
            'ball_type' => '作業',
            'task' => $task,
            'due_date' => $due,
            'status' => 'open',
            'created_by' => '堀内',
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
}
