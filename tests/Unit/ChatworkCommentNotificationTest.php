<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\Subject;
use App\Services\ChatworkService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatworkCommentNotificationTest extends TestCase
{
    public function test_a_new_comment_is_sent_to_chatwork_with_team_member_mentions(): void
    {
        config([
            'chatwork.enabled' => true,
            'chatwork.api_token' => 'token',
            'chatwork.room_id' => '394762259',
            'chatwork.project_base_url' => 'https://project.example.com',
            'chatwork.member_mapping' => [
                '堀内' => '1406764',
                '金子' => '1419661',
            ],
        ]);

        $project = new Project([
            'name' => '仕様確認',
            'team_members' => ['堀内', '金子', '未登録'],
        ]);
        $project->id = 12;
        $project->setRelation('subject', new Subject(['name' => 'テスト']));

        Http::fake([
            'https://api.chatwork.com/*' => Http::response(['message_id' => '1'], 200),
        ]);

        app(ChatworkService::class)->notifyNewComment(
            $project,
            '星',
            "https://docs.google.com/spreadsheets/d/abc/edit?usp=drive_link",
        );

        Http::assertSent(function ($request) {
            $body = $request['body'] ?? '';

            return $request->url() === 'https://api.chatwork.com/v2/rooms/394762259/messages'
                && str_contains($body, '[To:1406764][To:1419661]')
                && ! str_contains($body, '[To:未登録]')
                && str_contains($body, '【テスト > 仕様確認】に新しいコメントが追加されました')
                && str_contains($body, "投稿者: 星")
                && str_contains($body, 'https://docs.google.com/spreadsheets/d/abc/edit?usp=drive_link')
                && str_contains($body, 'https://project.example.com/projects/12');
        });
    }
}
