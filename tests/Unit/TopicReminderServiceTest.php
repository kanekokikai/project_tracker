<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\Subject;
use App\Services\TopicReminderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TopicReminderServiceTest extends TestCase
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

    public function test_notices_follow_the_meeting_date_and_mention_the_topic_members(): void
    {
        $this->travelTo(Carbon::parse('2026-11-23 08:30:00', 'Asia/Tokyo'));

        $twoWeeks = $this->topic('コンベア', '異音', ['堀内', '金子'], '2026-12-07');
        $oneWeek = $this->topic('見積', '回答', ['金子'], '2026-11-30');
        $soon = $this->topic('組立', '確認', ['堀内'], '2026-11-26');
        $overdue = $this->topic('搬送', '停止', ['星'], '2026-11-22');
        $this->topic('先の話', 'まだ先', ['堀内'], '2026-12-20');
        $this->topic('古い案件', '完了した議題', ['堀内'], '2026-11-22', '完了');
        $this->topic('期限なし', '日付なし', ['堀内'], null);
        $this->topic('無人', 'メンバーなし', [], '2026-12-07');

        $sent = app(TopicReminderService::class)->send();

        $this->assertSame(4, $sent);
        Http::assertSentCount(4);

        $bodies = $this->bodies();
        $twoWeekBody = $this->bodyContaining($bodies, 'この議題の会議が2週間後です');
        $oneWeekBody = $this->bodyContaining($bodies, 'この議題の会議が1週間後です');
        $soonBody = $this->bodyContaining($bodies, 'この議題の会議が迫っています。準備はできていますか？');
        $overdueBody = $this->bodyContaining($bodies, '会議の日を過ぎた議題があります。対応してください');

        $this->assertStringContainsString('[To:1406764]', $twoWeekBody);
        $this->assertStringContainsString('[To:1419661]', $twoWeekBody);
        $this->assertStringContainsString('【コンベア > 異音】', $twoWeekBody);
        $this->assertStringContainsString('会議 2026/12/7', $twoWeekBody);
        $this->assertStringContainsString('https://project.example.com/projects/'.$twoWeeks->id, $twoWeekBody);

        $this->assertStringContainsString('[To:1419661]', $oneWeekBody);
        $this->assertStringNotContainsString('[To:1406764]', $oneWeekBody);
        $this->assertStringContainsString('会議 2026/11/30', $oneWeekBody);

        $this->assertStringContainsString('【組立 > 確認】', $soonBody);
        $this->assertStringContainsString('会議 2026/11/26', $soonBody);
        $this->assertStringContainsString('【搬送 > 停止】', $overdueBody);
        $this->assertStringContainsString('[To:2215971]', $overdueBody);
        $this->assertStringNotContainsString('まだ先', implode("\n", $bodies));
        $this->assertStringNotContainsString('完了した議題', implode("\n", $bodies));
        $this->assertStringNotContainsString('日付なし', implode("\n", $bodies));
        $this->assertStringNotContainsString('メンバーなし', implode("\n", $bodies));
    }

    public function test_a_two_week_notice_is_sent_once_and_the_daily_chase_repeats_each_slot(): void
    {
        $this->travelTo(Carbon::parse('2026-11-23 08:30:00', 'Asia/Tokyo'));
        $this->topic('コンベア', '異音', ['堀内'], '2026-12-07');
        $this->topic('組立', '確認', ['堀内'], '2026-11-26');

        $this->assertSame(2, app(TopicReminderService::class)->send());
        $this->assertSame(0, app(TopicReminderService::class)->send());

        $this->travelTo(Carbon::parse('2026-11-23 13:00:00', 'Asia/Tokyo'));
        $this->assertSame(1, app(TopicReminderService::class)->send());
        Http::assertSentCount(3);

        $bodies = $this->bodies();
        $this->assertCount(1, array_filter($bodies, fn (string $body) => str_contains($body, '2週間後')));
        $this->assertCount(2, array_filter($bodies, fn (string $body) => str_contains($body, '迫っています')));
    }

    public function test_a_missed_morning_notice_goes_out_in_the_afternoon(): void
    {
        $this->travelTo(Carbon::parse('2026-11-23 13:00:00', 'Asia/Tokyo'));
        $this->topic('コンベア', '異音', ['堀内'], '2026-12-07');

        $this->assertSame(1, app(TopicReminderService::class)->send());
        $this->assertSame(0, app(TopicReminderService::class)->send());
        Http::assertSentCount(1);
    }

    public function test_weekends_stay_quiet_and_a_new_date_can_be_announced_again(): void
    {
        $this->travelTo(Carbon::parse('2026-11-23 08:30:00', 'Asia/Tokyo'));
        $topic = $this->topic('コンベア', '異音', ['堀内'], '2026-12-07');
        $this->assertSame(1, app(TopicReminderService::class)->send());

        $topic->update(['due_date' => '2026-12-14']);

        $this->travelTo(Carbon::parse('2026-11-28 09:00:00', 'Asia/Tokyo'));
        $this->assertSame(0, app(TopicReminderService::class)->send());

        $this->travelTo(Carbon::parse('2026-11-30 08:30:00', 'Asia/Tokyo'));
        $this->assertSame(1, app(TopicReminderService::class)->send());
        $this->assertStringContainsString('2週間後', $this->bodies()[1]);
    }

    public function test_the_artisan_command_sends_the_current_slot(): void
    {
        $this->travelTo(Carbon::parse('2026-12-04 08:30:00', 'Asia/Tokyo'));
        $this->topic('コンベア', '異音', ['堀内'], '2026-12-07');

        $this->artisan('topics:remind')
            ->expectsOutput('お知らせを1通送りました')
            ->assertSuccessful();

        Http::assertSentCount(1);
        $this->assertStringContainsString('迫っています', $this->bodies()[0]);
        $this->assertStringNotContainsString('2週間後', $this->bodies()[0]);
    }

    /**
     * @param  list<string>  $members
     */
    private function topic(string $subject, string $name, array $members, ?string $due, string $status = '進行中'): Project
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
            'due_date' => $due,
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

        $this->fail('お知らせ文面に「'.$needle.'」がありません');
    }
}
