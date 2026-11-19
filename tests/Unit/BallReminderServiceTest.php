<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\ProjectBall;
use App\Models\Subject;
use App\Services\BallReminderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BallReminderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'chatwork.enabled' => true,
            'chatwork.api_token' => 'token',
            'chatwork.room_id' => '394762259',
            'chatwork.project_base_url' => 'https://project.example.com',
        ]);

        Http::fake([
            'https://api.chatwork.com/*' => Http::response(['message_id' => '1'], 200),
        ]);
    }

    public function test_approaching_balls_mention_only_the_holder_and_overdue_balls_mention_the_whole_team(): void
    {
        $this->travelTo(Carbon::parse('2026-11-06 08:30:00', 'Asia/Tokyo'));

        $noise = $this->topic('コンベア', '異音', ['堀内', '金子']);
        $this->ball($noise, '堀内', '今日やる', '2026-11-06');
        $this->ball($noise, '堀内', '確認する', '2026-11-09');
        $this->ball($noise, '星', '直す', '2026-11-05');
        $this->ball($noise, '外部（客先・業者）', '返事を待つ', '2026-11-05', '外部');
        $this->ball($noise, '堀内', '済み', '2026-11-01', '作業', 'done');
        $this->ball($noise, '堀内', 'まだ先', '2026-11-20');

        $quote = $this->topic('見積', '回答', ['金子']);
        $this->ball($quote, '金子', '返事する', '2026-11-01');

        $finished = $this->topic('古い案件', '完了した議題', ['堀内'], '完了');
        $this->ball($finished, '堀内', '放置', '2026-11-01');

        $sent = app(BallReminderService::class)->send();

        $this->assertSame(3, $sent);
        Http::assertSentCount(3);

        $bodies = $this->bodies();
        $approaching = $this->bodyContaining($bodies, 'このボールの期限迫ってます。やっていますか？');
        $noiseOverdue = $this->bodyContaining($bodies, '直す');
        $quoteOverdue = $this->bodyContaining($bodies, '返事する');

        $this->assertStringContainsString('[To:1406764]', $approaching);
        $this->assertStringNotContainsString('[To:1419661]', $approaching);
        $this->assertStringNotContainsString('[To:2215971]', $approaching);
        $this->assertStringContainsString('【コンベア > 異音】', $approaching);
        $this->assertStringContainsString('今日やる（期限 11/6）', $approaching);
        $this->assertStringContainsString('確認する（期限 11/9）', $approaching);
        $this->assertStringContainsString('https://project.example.com/projects/'.$noise->id, $approaching);
        $this->assertStringNotContainsString('まだ先', $approaching);
        $this->assertStringNotContainsString('返事を待つ', $approaching);
        $this->assertStringNotContainsString('済み', $approaching);
        $this->assertStringNotContainsString('放置', $approaching);

        $this->assertStringContainsString('期限が過ぎたボールがあるから対応してください', $noiseOverdue);
        $this->assertStringContainsString('[To:1406764]', $noiseOverdue);
        $this->assertStringContainsString('[To:1419661]', $noiseOverdue);
        $this->assertStringContainsString('[To:2215971]', $noiseOverdue);
        $this->assertStringContainsString('星さん: 直す（期限 11/5）', $noiseOverdue);

        $this->assertStringContainsString('[To:1419661]', $quoteOverdue);
        $this->assertStringNotContainsString('[To:1406764]', $quoteOverdue);
        $this->assertStringContainsString('https://project.example.com/projects/'.$quote->id, $quoteOverdue);
    }

    public function test_the_same_slot_is_sent_once_and_the_afternoon_slot_sends_again(): void
    {
        $this->travelTo(Carbon::parse('2026-11-06 08:30:00', 'Asia/Tokyo'));
        $topic = $this->topic('コンベア', '異音', ['堀内']);
        $this->ball($topic, '堀内', '確認する', '2026-11-09');

        $this->assertSame(1, app(BallReminderService::class)->send());
        $this->assertSame(0, app(BallReminderService::class)->send());
        Http::assertSentCount(1);

        $this->travelTo(Carbon::parse('2026-11-06 13:00:00', 'Asia/Tokyo'));
        $this->assertSame(1, app(BallReminderService::class)->send());
        Http::assertSentCount(2);

        $this->travelTo(Carbon::parse('2026-11-07 09:00:00', 'Asia/Tokyo'));
        $this->assertSame(0, app(BallReminderService::class)->send());
        Http::assertSentCount(2);
    }

    public function test_the_artisan_command_sends_the_current_slot(): void
    {
        $this->travelTo(Carbon::parse('2026-11-06 08:30:00', 'Asia/Tokyo'));
        $topic = $this->topic('コンベア', '異音', ['堀内']);
        $this->ball($topic, '堀内', '確認する', '2026-11-09');

        $this->artisan('balls:remind')
            ->expectsOutput('催促を1通送りました')
            ->assertSuccessful();

        Http::assertSentCount(1);
    }

    /**
     * @param  list<string>  $members
     */
    private function topic(string $subject, string $name, array $members, string $status = '進行中'): Project
    {
        $subjectModel = Subject::query()->create([
            'name' => $subject,
            'created_by' => 'テスト',
        ]);

        return Project::query()->create([
            'name' => $name,
            'status' => $status,
            'subject_id' => $subjectModel->id,
            'team_members' => $members,
            'department' => '選択なし',
        ]);
    }

    private function ball(
        Project $project,
        string $assignee,
        string $task,
        string $due,
        string $type = '作業',
        string $status = 'open',
    ): ProjectBall {
        return ProjectBall::query()->create([
            'project_id' => $project->id,
            'assignee' => $assignee,
            'ball_type' => $type,
            'task' => $task,
            'due_date' => $due,
            'status' => $status,
            'created_by' => 'テスト',
        ]);
    }

    /**
     * @return list<string>
     */
    private function bodies(): array
    {
        return Http::recorded()
            ->map(fn ($pair) => (string) ($pair[0]['body'] ?? ''))
            ->all();
    }

    /**
     * @param  list<string>  $bodies
     */
    private function bodyContaining(array $bodies, string $needle): string
    {
        foreach ($bodies as $body) {
            if (str_contains($body, $needle)) {
                return $body;
            }
        }

        $this->fail('催促文面に「'.$needle.'」がありません');
    }
}
