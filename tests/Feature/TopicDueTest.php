<?php

namespace Tests\Feature;

use App\Models\ChatworkMember;
use App\Models\Project;
use App\Models\ProjectHistory;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class TopicDueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        URL::forceRootUrl('http://localhost');
    }

    public function test_a_topic_needs_a_meeting_date_or_an_explicit_blank(): void
    {
        $subject = $this->subject();

        $this->asMember()
            ->postJson('/projects', $this->payload($subject, []))
            ->assertStatus(422)
            ->assertJsonPath('message', '期限を選ぶか、期限なしにしてください');

        $this->asMember()
            ->postJson('/projects', $this->payload($subject, ['no_due' => 1, 'name' => '日付なし']))
            ->assertOk();

        $blank = Project::query()->where('name', '日付なし')->first();
        $this->assertNotNull($blank);
        $this->assertNull($blank->due_date);

        $this->asMember()
            ->postJson('/projects', $this->payload($subject, ['due_date' => '2026-12-07', 'name' => '異音']))
            ->assertOk();

        $dated = Project::query()->where('name', '異音')->firstOrFail();
        $this->assertSame('2026-12-07', $dated->due_date?->toDateString());
        $this->assertStringContainsString('会議 2026/12/7', (string) $dated->histories()->value('content'));
    }

    public function test_a_meeting_date_can_only_move_later_while_the_topic_is_open(): void
    {
        $subject = $this->subject();
        $created = $this->asMember()
            ->postJson('/projects', $this->payload($subject, ['due_date' => '2026-12-07']))
            ->assertOk()
            ->json();

        $project = Project::query()->findOrFail($created['id']);

        $this->asMember()
            ->putJson('/projects/'.$project->id, $this->payload($subject, ['no_due' => 1]))
            ->assertStatus(422)
            ->assertJsonPath('message', '付けた期限は消せません');

        $this->asMember()
            ->putJson('/projects/'.$project->id, $this->payload($subject, ['due_date' => '2026-12-01']))
            ->assertStatus(422)
            ->assertJsonPath('message', '今の期限より後の日を選んでください');

        $this->asMember()
            ->putJson('/projects/'.$project->id, $this->payload($subject, ['due_date' => '2026-12-14', 'name' => '延ばした議題']))
            ->assertOk();

        $project->refresh();
        $this->assertSame('2026-12-14', $project->due_date?->toDateString());
        $this->assertTrue(
            $project->histories()->where('content', '会議の日を 2026/12/7 から 2026/12/14 に延ばしました')->exists()
        );

        $project->update(['status' => '完了']);

        $this->asMember()
            ->putJson('/projects/'.$project->id, $this->payload($subject, ['due_date' => '2026-12-20', 'name' => '延ばした議題']))
            ->assertStatus(422)
            ->assertJsonPath('message', '完了か中止の議題の期限は変えられません');

        $this->asMember()
            ->postJson('/projects/'.$project->id.'/due', ['due_date' => '2026-12-21'])
            ->assertStatus(422);
    }

    public function test_the_room_can_set_and_extend_the_meeting_date(): void
    {
        $subject = $this->subject();
        $created = $this->asMember()
            ->postJson('/projects', $this->payload($subject, ['no_due' => 1]))
            ->assertOk()
            ->json();

        $project = Project::query()->findOrFail($created['id']);

        $this->asMember()
            ->postJson('/projects/'.$project->id.'/due', ['due_date' => '2026-12-07'])
            ->assertOk()
            ->assertJsonPath('message', '期限を付けました');

        $this->assertSame('2026-12-07', $project->refresh()->due_date?->toDateString());
        $this->assertTrue(
            ProjectHistory::query()->where('content', '会議の日を 2026/12/7 にしました')->exists()
        );

        $this->asMember()
            ->postJson('/projects/'.$project->id.'/due', ['due_date' => '2026-12-07'])
            ->assertStatus(422);

        $this->asMember()
            ->postJson('/projects/'.$project->id.'/due', ['due_date' => '2026-12-18'])
            ->assertOk()
            ->assertJsonPath('message', '期限を延ばしました');
    }

    public function test_lists_keep_the_date_on_the_existing_line_and_the_room_shows_the_countdown(): void
    {
        $this->travelTo(Carbon::parse('2026-11-19 09:00:00', 'Asia/Tokyo'));
        $subject = $this->subject();
        $created = $this->asMember()
            ->postJson('/projects', $this->payload($subject, ['due_date' => '2026-12-07', 'name' => '異音']))
            ->assertOk()
            ->json();

        $home = $this->get('/')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<span class="topic-due"[^>]*>\s*2026\/12\/7\s*<\/span>/', $home);
        $this->assertStringNotContainsString('topic-due-line', $home);

        $subjects = $this->get('/subjects')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<span class="topic-due"[^>]*>\s*2026\/12\/7\s*<\/span>/', $subjects);

        $room = $this->asMember()->get('/projects/'.$created['id'])->assertOk()->getContent();
        $this->assertStringContainsString('2026/12/7 あと18日', $room);
        $this->assertStringContainsString('期限を延ばす', $room);
        $this->assertDoesNotMatchRegularExpression('/<p[^>]*class="[^"]*topic-due/', $room);
    }

    private function subject(): Subject
    {
        return Subject::query()->create([
            'name' => 'コンベア',
            'created_by' => 'テスト',
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function payload(Subject $subject, array $extra): array
    {
        return array_merge([
            'name' => '異音',
            'subject_id' => $subject->id,
            'department' => '選択なし',
            'team_members' => '[]',
        ], $extra);
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
