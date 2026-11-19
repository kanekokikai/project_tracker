<?php

namespace App\Services;

use App\Models\ChatworkMember;
use App\Models\Project;
use App\Models\ProjectBall;
use App\Support\BallDisplay;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatworkService
{
    public function isEnabled(): bool
    {
        return config('chatwork.enabled')
            && config('chatwork.api_token')
            && config('chatwork.room_id');
    }

    public function notifyNewComment(Project $project, string $author, string $content): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $project->loadMissing('subject');
        $subjectName = trim((string) ($project->subject?->name ?? ''));
        $fullProjectName = $subjectName !== ''
            ? "{$subjectName} > {$project->name}"
            : $project->name;
        $teamMembers = array_values(array_filter(
            is_array($project->team_members) ? $project->team_members : [],
            fn ($member) => is_string($member) && $member !== ''
        ));

        $message = "【{$fullProjectName}】に新しいコメントが追加されました\n\n";
        $message .= "投稿者: {$author}\n";
        $message .= "コメント内容:\n{$content}";

        $projectUrl = $this->projectUrl($project);

        if ($projectUrl !== '') {
            $message .= "\n\n▼プロジェクトを確認\n{$projectUrl}";
        }

        $this->send($teamMembers, $message);
    }

    /**
     * @param  list<ProjectBall>  $balls
     */
    public function notifyBallsAssigned(Project $project, array $balls): void
    {
        if (! $this->isEnabled() || $balls === []) {
            return;
        }

        $project->loadMissing('subject');
        $subjectName = $project->subject?->name ?: $project->name;
        $url = route('projects.show', $project);
        $grouped = [];

        foreach ($balls as $ball) {
            if ($ball->ball_type === '外部' || $ball->assignee === BallDisplay::EXTERNAL) {
                continue;
            }

            $grouped[$ball->assignee][] = $ball;
        }

        $mapping = ChatworkMember::mapping();

        foreach ($grouped as $assignee => $items) {
            if (empty($mapping[$assignee])) {
                continue;
            }

            $lines = ["【{$subjectName}】のボールがあなたに回りました"];

            foreach ($items as $ball) {
                $due = $ball->due_date
                    ? '期限 '.BallDisplay::monthDay($ball->due_date->toDateString())
                    : '期限なし';
                $lines[] = $ball->task.' / '.$due;
            }

            $lines[] = '';
            $lines[] = '▼会議室を開く';
            $lines[] = $url;

            $this->send([$assignee], implode("\n", $lines));
        }
    }

    /**
     * @param  list<ProjectBall>  $balls
     */
    public function remindApproaching(string $assignee, array $balls): bool
    {
        if (! $this->isEnabled() || $balls === [] || ! $this->hasMention($assignee)) {
            return false;
        }

        $lines = ['このボールの期限迫ってます。やっていますか？'];
        $grouped = [];

        foreach ($balls as $ball) {
            $grouped[$ball->project_id][] = $ball;
        }

        foreach ($grouped as $items) {
            $project = $items[0]->project;
            $lines[] = '';
            $lines[] = '【'.$this->fullProjectName($project).'】';

            foreach ($items as $ball) {
                $due = BallDisplay::monthDay($ball->due_date?->toDateString());
                $lines[] = $ball->task.'（期限 '.$due.'）';
            }

            $lines[] = '▼会議室を開く';
            $lines[] = $this->projectUrl($project);
        }

        return $this->send([$assignee], implode("\n", $lines));
    }

    /**
     * @param  list<ProjectBall>  $balls
     * @param  list<string>  $members
     */
    public function remindOverdue(Project $project, array $balls, array $members): bool
    {
        $members = array_values(array_filter($members, fn (string $member) => $this->hasMention($member)));

        if (! $this->isEnabled() || $balls === [] || $members === []) {
            return false;
        }

        $lines = [
            '期限が過ぎたボールがあるから対応してください',
            '',
            '【'.$this->fullProjectName($project).'】',
        ];

        foreach ($balls as $ball) {
            $due = BallDisplay::monthDay($ball->due_date?->toDateString());
            $lines[] = BallDisplay::personLabel($ball->assignee).': '.$ball->task.'（期限 '.$due.'）';
        }

        $lines[] = '';
        $lines[] = '▼会議室を開く';
        $lines[] = $this->projectUrl($project);

        return $this->send($members, implode("\n", $lines));
    }

    /**
     * @param  list<string>  $teamMembers
     */
    public function send(array $teamMembers, string $message): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $roomId = config('chatwork.room_id');
        $apiToken = config('chatwork.api_token');
        $body = $this->buildMessageBody($teamMembers, $message);

        try {
            $response = Http::withHeaders([
                'X-ChatWorkToken' => $apiToken,
            ])->timeout(4)->connectTimeout(2)->asForm()->post(
                "https://api.chatwork.com/v2/rooms/{$roomId}/messages",
                ['body' => $body]
            );

            if ($response->successful()) {
                return true;
            }

            Log::warning('Chatwork API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Chatwork notification failed', [
                'message' => $exception->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * @param  list<string>  $teamMembers
     */
    public function buildMessageBody(array $teamMembers, string $message): string
    {
        if ($teamMembers === []) {
            return $message;
        }

        $mapping = ChatworkMember::mapping();
        $toPrefix = '';

        foreach ($teamMembers as $member) {
            $chatworkAccount = $mapping[$member] ?? '';

            if ($chatworkAccount === '' || ! ctype_digit((string) $chatworkAccount)) {
                continue;
            }

            $toPrefix .= "[To:{$chatworkAccount}]";
        }

        return $toPrefix."\n".$message;
    }

    private function fullProjectName(Project $project): string
    {
        $project->loadMissing('subject');
        $subjectName = trim((string) ($project->subject?->name ?? ''));

        return $subjectName !== ''
            ? "{$subjectName} > {$project->name}"
            : $project->name;
    }

    private function hasMention(string $member): bool
    {
        $account = ChatworkMember::mapping()[$member] ?? '';

        return $account !== '' && ctype_digit((string) $account);
    }

    private function projectUrl(Project $project): string
    {
        $base = rtrim((string) config('chatwork.project_base_url', ''), '/');

        if ($base !== '') {
            return $base.'/projects/'.$project->id;
        }

        return route('projects.show', $project);
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    public function fetchRoomMembers(): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $roomId = config('chatwork.room_id');
        $apiToken = config('chatwork.api_token');

        try {
            $response = Http::withHeaders([
                'X-ChatWorkToken' => $apiToken,
            ])->timeout(4)->connectTimeout(2)->get("https://api.chatwork.com/v2/rooms/{$roomId}/members");

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Chatwork room members fetch failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Chatwork room members fetch exception', [
                'message' => $exception->getMessage(),
            ]);
        }

        return null;
    }
}
