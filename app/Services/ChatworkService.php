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
